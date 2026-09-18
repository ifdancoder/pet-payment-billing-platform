<?php

namespace App\Application\Payment\Ports\Outbound;

use App\Domain\Payment\Exceptions\PaymentNotFound;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface IPaymentRepositoryPort
{
    public function save(Payment $payment): void;

    /**
     * @throws PaymentNotFound
     */
    public function get(PaymentId $id, MerchantId $merchantId): Payment;

    /**
     * @return array<int, Payment>
     */
    public function all(MerchantId $merchantId): array;

    /**
     * Looks up the payment already created for this invoice, so a handler
     * can treat a redelivered or duplicate trigger as a no-op instead of
     * creating a second payment for the same invoice.
     */
    public function findByInvoiceId(InvoiceId $invoiceId): ?Payment;
}
