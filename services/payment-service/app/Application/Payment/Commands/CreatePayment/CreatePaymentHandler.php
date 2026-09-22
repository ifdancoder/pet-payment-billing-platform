<?php

namespace App\Application\Payment\Commands\CreatePayment;

use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreatePaymentHandler
{
    public function __construct(
        private readonly IPaymentRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CreatePaymentCommand $command): ?Payment
    {
        return $this->transaction->run(function () use ($command): ?Payment {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $invoiceId = InvoiceId::fromString($command->invoiceId);

            // Inbox handles redelivery; invoice ID handles distinct events for the same invoice.
            $existing = $this->repository->findByInvoiceId($invoiceId);
            if ($existing !== null) {
                return $existing;
            }

            $payment = Payment::create(
                PaymentId::generate(),
                $invoiceId,
                MerchantId::fromString($command->merchantId),
                CustomerId::fromString($command->customerId),
                Money::of($command->amountMinorUnits, Currency::from($command->currency)),
                $command->billingReason,
            );

            try {
                $this->repository->save($payment);
            } catch (UniqueConstraintViolationException) {
                // A concurrent delivery committed this payment first.
                return $this->repository->findByInvoiceId($invoiceId);
            }

            return $payment;
        });
    }
}
