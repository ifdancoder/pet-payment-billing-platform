<?php

namespace App\Application\Invoice\Commands\VoidInvoice;

use App\Application\Invoice\IntegrationEvents\InvoiceVoidedIntegrationEvent;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Events\InvoiceVoided;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class VoidInvoiceHandler
{
    public function __construct(
        private readonly IInvoiceRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(VoidInvoiceCommand $command): Invoice
    {
        $invoice = $this->repository->get(
            InvoiceId::fromString($command->invoiceId),
            MerchantId::fromString($command->merchantId),
        );

        $invoice->void(new DateTimeImmutable);

        $this->transaction->run(function () use ($invoice): void {
            $this->repository->save($invoice);
            $this->recordIntegrationEvents($invoice);
        });

        return $invoice;
    }

    private function recordIntegrationEvents(Invoice $invoice): void
    {
        foreach ($invoice->pullRecordedEvents() as $event) {
            if ($event instanceof InvoiceVoided) {
                $this->outbox->add(InvoiceVoidedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
