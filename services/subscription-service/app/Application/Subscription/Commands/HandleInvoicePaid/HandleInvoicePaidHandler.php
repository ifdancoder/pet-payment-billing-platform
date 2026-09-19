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

/**
 * Reacts to invoice.paid.v1 by activating the Subscription. Deliberately
 * a separate handler from ActivateSubscriptionHandler (the existing
 * HTTP-triggered one) rather than reusing it directly: that one has no
 * Inbox guard because an HTTP caller has no event_id to guard with, and
 * bolting an optional Inbox check onto it would blur which call sites
 * need at-least-once redelivery safety and which don't. Both ultimately
 * call the same Subscription::activate().
 *
 * Skips the transition when Canceled: a late/retried payment succeeding
 * after the customer already canceled must not silently resurrect the
 * subscription. Every other status is a legitimate case for
 * activate()'s own idempotency to handle (Pending/PastDue -> Active,
 * Active -> Active no-op).
 */
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
                $subscription->activate();
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
