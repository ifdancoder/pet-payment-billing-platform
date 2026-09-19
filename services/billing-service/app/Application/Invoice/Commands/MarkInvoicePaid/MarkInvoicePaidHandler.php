<?php

namespace App\Application\Invoice\Commands\MarkInvoicePaid;

use App\Application\Invoice\IntegrationEvents\InvoicePaidIntegrationEvent;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Events\InvoicePaid;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class MarkInvoicePaidHandler
{
    public function __construct(
        private readonly IInvoiceRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    /** Adds Billing's subscription ID to the outgoing invoice event. */
    public function handle(MarkInvoicePaidCommand $command): ?Invoice
    {
        return $this->transaction->run(function () use ($command): ?Invoice {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $invoice = $this->repository->get(
                InvoiceId::fromString($command->invoiceId),
                MerchantId::fromString($command->merchantId),
            );

            $invoice->markPaid(PaymentId::fromString($command->paymentId), $command->paidAt);

            $this->repository->save($invoice);
            $this->recordIntegrationEvents($invoice);

            return $invoice;
        });
    }

    private function recordIntegrationEvents(Invoice $invoice): void
    {
        foreach ($invoice->pullRecordedEvents() as $event) {
            if ($event instanceof InvoicePaid) {
                $this->outbox->add(InvoicePaidIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
