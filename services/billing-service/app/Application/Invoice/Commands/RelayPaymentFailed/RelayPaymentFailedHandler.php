<?php

namespace App\Application\Invoice\Commands\RelayPaymentFailed;

use App\Application\Invoice\IntegrationEvents\InvoicePaymentFailedIntegrationEvent;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

/** Adds Billing's subscription ID without changing the Open invoice. */
final class RelayPaymentFailedHandler
{
    public function __construct(
        private readonly IInvoiceRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(RelayPaymentFailedCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return;
            }

            $invoice = $this->repository->get(
                InvoiceId::fromString($command->invoiceId),
                MerchantId::fromString($command->merchantId),
            );

            $this->outbox->add(InvoicePaymentFailedIntegrationEvent::of(
                $invoice->id()->toString(),
                $invoice->merchantId()->toString(),
                $invoice->customerId()->toString(),
                $invoice->subscriptionId()->toString(),
                $command->paymentId,
                $command->failureCode,
            ));
        });
    }
}
