<?php

namespace App\Domain\Payment\Events;

use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class PaymentFailed
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly InvoiceId $invoiceId,
        public readonly MerchantId $merchantId,
        public readonly CustomerId $customerId,
        public readonly PaymentAttemptId $paymentAttemptId,
        public readonly string $failureCode,
        public readonly DateTimeImmutable $failedAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
