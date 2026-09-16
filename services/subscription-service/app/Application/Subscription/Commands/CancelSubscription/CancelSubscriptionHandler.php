<?php

namespace App\Application\Subscription\Commands\CancelSubscription;

use App\Application\Subscription\IntegrationEvents\SubscriptionCanceledIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionCanceled;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class CancelSubscriptionHandler
{
    public function __construct(
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CancelSubscriptionCommand $command): Subscription
    {
        $subscription = $this->repository->get(
            SubscriptionId::fromString($command->subscriptionId),
            MerchantId::fromString($command->merchantId),
        );

        $subscription->cancel();

        $this->transaction->run(function () use ($subscription): void {
            $this->repository->save($subscription);
            $this->recordIntegrationEvents($subscription);
        });

        return $subscription;
    }

    private function recordIntegrationEvents(Subscription $subscription): void
    {
        foreach ($subscription->pullRecordedEvents() as $event) {
            if ($event instanceof SubscriptionCanceled) {
                $this->outbox->add(SubscriptionCanceledIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
