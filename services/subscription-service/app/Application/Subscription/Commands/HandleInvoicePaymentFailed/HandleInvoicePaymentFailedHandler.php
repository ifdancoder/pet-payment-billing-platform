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

/** Initial payment failure stays Pending; renewal failure moves Active to PastDue. */
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
