<?php

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\ValueObjects\PaymentId;
use RuntimeException;

final class PaymentNotFound extends RuntimeException
{
    public static function withId(PaymentId $id): self
    {
        return new self("Payment \"{$id->toString()}\" was not found.");
    }
}
