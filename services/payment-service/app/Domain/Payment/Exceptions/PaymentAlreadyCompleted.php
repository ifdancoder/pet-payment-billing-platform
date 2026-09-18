<?php

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\ValueObjects\PaymentId;
use RuntimeException;

final class PaymentAlreadyCompleted extends RuntimeException
{
    public static function withId(PaymentId $id): self
    {
        return new self("Payment \"{$id->toString()}\" has already succeeded and cannot be retried.");
    }
}
