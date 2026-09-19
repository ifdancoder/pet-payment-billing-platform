<?php

namespace App\Application\Invoice\Commands\MarkInvoicePaid;

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class MarkInvoicePaidHandler
{
    public function __construct(
        private readonly IInvoiceRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    /**
     * Marks the Invoice Paid, or returns null without doing anything
     * when this exact event has already been processed (an
     * at-least-once redelivery of payment.succeeded.v1). No outbox
     * write here — nothing downstream consumes "an invoice was paid"
     * today, so there's no integration event to add for symmetry alone.
     */
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

            return $invoice;
        });
    }
}
