<?php

namespace App\Application\Subscription\Commands\CreateSubscription;

use App\Application\Subscription\Exceptions\CustomerNotFound;
use App\Application\Subscription\Exceptions\PriceIsNotActive;
use App\Application\Subscription\Exceptions\PriceIsNotRecurring;
use App\Application\Subscription\Exceptions\PriceNotFound;
use App\Application\Subscription\IntegrationEvents\SubscriptionCreatedIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ICatalogGatewayPort;
use App\Application\Subscription\Ports\Outbound\ICustomerGatewayPort;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class CreateSubscriptionHandler
{
    public function __construct(
        private readonly ICustomerGatewayPort $customers,
        private readonly ICatalogGatewayPort $catalog,
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CreateSubscriptionCommand $command): Subscription
    {
        $merchantId = MerchantId::fromString($command->merchantId);
        $customerId = CustomerId::fromString($command->customerId);
        $priceId = PriceId::fromString($command->priceId);

        // Both external calls happen before any transaction opens, so the
        // DB transaction below stays short and never waits on the network.
        if ($this->customers->find($merchantId, $customerId) === null) {
            throw CustomerNotFound::withId($customerId);
        }

        $priceData = $this->catalog->findPrice($merchantId, $priceId);

        if ($priceData === null) {
            throw PriceNotFound::withId($priceId);
        }

        if (! $priceData->isRecurring()) {
            throw PriceIsNotRecurring::withId($priceId);
        }

        if (! $priceData->isActive()) {
            throw PriceIsNotActive::withId($priceId);
        }

        $priceSnapshot = PriceSnapshot::of(
            PriceId::fromString($priceData->priceId),
            ProductId::fromString($priceData->productId),
            Money::of($priceData->amountMinorUnits, Currency::from($priceData->currency)),
            BillingPeriod::of(BillingInterval::fromLabel($priceData->billingInterval), $priceData->billingIntervalCount),
        );

        $subscription = Subscription::create(SubscriptionId::generate(), $merchantId, $customerId, $priceSnapshot);

        $this->transaction->run(function () use ($subscription): void {
            $this->repository->save($subscription);
            $this->recordIntegrationEvents($subscription);
        });

        return $subscription;
    }

    private function recordIntegrationEvents(Subscription $subscription): void
    {
        foreach ($subscription->pullRecordedEvents() as $event) {
            if ($event instanceof SubscriptionCreated) {
                $this->outbox->add(SubscriptionCreatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
