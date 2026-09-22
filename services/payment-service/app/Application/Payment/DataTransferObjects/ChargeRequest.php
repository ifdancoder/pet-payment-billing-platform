<?php

namespace App\Application\Payment\DataTransferObjects;

use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;

final readonly class ChargeRequest
{
    public function __construct(
        /**
         * Used as the provider idempotency key. A retry of the same
         * attempt must reuse this exact id, never generate a new one —
         * otherwise the provider sees two unrelated operations instead
         * of one safely-repeated request.
         */
        public PaymentAttemptId $attemptId,
        public Money $money,
        public string $billingReason = 'subscription_create',
    ) {}
}
