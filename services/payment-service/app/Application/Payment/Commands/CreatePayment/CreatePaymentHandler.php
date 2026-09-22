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

    /**
     * Records a Pending Payment for this invoice, or returns null without
     * doing anything when this exact event has already been processed (an
     * at-least-once redelivery of invoice.created.v1).
     *
     * No outbox write here: nothing downstream needs "a payment was
     * created" as a fact — only its eventual succeeded/failed outcome
     * does, and that's ProcessPaymentHandler's job.
     */
    public function handle(CreatePaymentCommand $command): ?Payment
    {
        return $this->transaction->run(function () use ($command): ?Payment {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            $invoiceId = InvoiceId::fromString($command->invoiceId);

            // A second, different event for the same invoice must not
            // create a second payment either — business idempotency on
            // top of the Inbox's event-id dedup.
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
                // Lost a race with a concurrent delivery for the same
                // invoice — the winner's payment is the truth.
                return $this->repository->findByInvoiceId($invoiceId);
            }

            return $payment;
        });
    }
}
