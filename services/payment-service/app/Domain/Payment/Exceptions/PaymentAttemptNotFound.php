<?php

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use RuntimeException;

final class PaymentAttemptNotFound extends RuntimeException
{
    public static function withId(PaymentAttemptId $id): self
    {
        return new self("Payment attempt \"{$id->toString()}\" was not found on this payment.");
    }
}
