<?php

namespace App\Application\Subscription\Commands\ActivateSubscription;

use App\Application\Subscription\IntegrationEvents\SubscriptionActivatedIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ActivateSubscriptionHandler
{
    public function __construct(
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(ActivateSubscriptionCommand $command): Subscription
    {
        $subscription = $this->repository->get(
            SubscriptionId::fromString($command->subscriptionId),
            MerchantId::fromString($command->merchantId),
        );

        $subscription->activate();

        $this->transaction->run(function () use ($subscription): void {
            $this->repository->save($subscription);
            $this->recordIntegrationEvents($subscription);
        });

        return $subscription;
    }

    private function recordIntegrationEvents(Subscription $subscription): void
    {
        foreach ($subscription->pullRecordedEvents() as $event) {
            if ($event instanceof SubscriptionActivated) {
                $this->outbox->add(SubscriptionActivatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
