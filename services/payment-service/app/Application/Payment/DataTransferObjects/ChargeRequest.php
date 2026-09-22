<?php

namespace App\Application\Payment\DataTransferObjects;

use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;

final readonly class ChargeRequest
{
    public function __construct(

        public PaymentAttemptId $attemptId,
        public Money $money,
        public string $billingReason = 'subscription_create',
    ) {}
}
