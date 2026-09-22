<?php

namespace App\Application\Subscription\Commands\HandleInvoicePaid;

use App\Application\Subscription\IntegrationEvents\SubscriptionActivatedIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

/** Handles event redelivery and never reactivates a canceled subscription. */
final class HandleInvoicePaidHandler
{
    public function __construct(
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(HandleInvoicePaidCommand $command): ?Subscription
    {
        return $this->transaction->run(function () use ($command): ?Subscription {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $subscription = $this->repository->get(
                SubscriptionId::fromString($command->subscriptionId),
                MerchantId::fromString($command->merchantId),
            );

            if ($subscription->status() !== SubscriptionStatus::Canceled) {
                $subscription->invoicePaid();
            }

            $this->repository->save($subscription);
            $this->recordIntegrationEvents($subscription);

            return $subscription;
        });
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
