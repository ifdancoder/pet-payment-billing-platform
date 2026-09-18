<?php

namespace App\Domain\Payment\Events;

use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class PaymentSucceeded
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly InvoiceId $invoiceId,
        public readonly MerchantId $merchantId,
        public readonly CustomerId $customerId,
        public readonly Money $money,
        public readonly PaymentAttemptId $paymentAttemptId,
        public readonly ProviderReference $providerReference,
        public readonly DateTimeImmutable $paidAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
