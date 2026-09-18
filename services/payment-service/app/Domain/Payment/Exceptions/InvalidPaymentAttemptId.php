<?php

namespace App\Domain\Payment\Exceptions;

use InvalidArgumentException;

final class InvalidPaymentAttemptId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid payment attempt id.");
    }
}
