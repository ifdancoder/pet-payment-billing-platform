<?php

namespace App\Application\Subscription\Commands\HandleInvoicePaymentFailed;

use App\Application\Subscription\IntegrationEvents\SubscriptionMarkedPastDueIntegrationEvent;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Events\SubscriptionMarkedPastDue;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

/**
 * Reacts to invoice.payment_failed.v1 by marking the Subscription
 * PastDue. Deliberately a separate handler from
 * MarkSubscriptionPastDueHandler (the existing HTTP-triggered one) —
 * same reasoning as HandleInvoicePaidHandler alongside
 * ActivateSubscriptionHandler.
 *
 * Only transitions when Active. A Pending subscription's very first
 * payment attempt can fail before it's ever been activated —
 * Subscription::markPastDue() only knows Active -> PastDue, and
 * "the first payment didn't go through yet" isn't the same fact as
 * "this previously-paying subscription is now overdue", so that case
 * is left Pending rather than forced through a transition that doesn't
 * apply to it.
 */
final class HandleInvoicePaymentFailedHandler
{
    public function __construct(
        private readonly ISubscriptionRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(HandleInvoicePaymentFailedCommand $command): ?Subscription
    {
        return $this->transaction->run(function () use ($command): ?Subscription {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $subscription = $this->repository->get(
                SubscriptionId::fromString($command->subscriptionId),
                MerchantId::fromString($command->merchantId),
            );

            $subscription->invoicePaymentFailed();

            $this->repository->save($subscription);
            $this->recordIntegrationEvents($subscription);

            return $subscription;
        });
    }

    private function recordIntegrationEvents(Subscription $subscription): void
    {
        foreach ($subscription->pullRecordedEvents() as $event) {
            if ($event instanceof SubscriptionMarkedPastDue) {
                $this->outbox->add(SubscriptionMarkedPastDueIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
