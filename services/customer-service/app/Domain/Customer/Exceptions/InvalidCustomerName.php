<?php

namespace App\Domain\Customer\Exceptions;

use InvalidArgumentException;

final class InvalidCustomerName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Customer name must not be empty.');
    }

    public static function tooLong(string $value): self
    {
        return new self(sprintf('Customer name must not be longer than 255 characters, got %d.', mb_strlen($value)));
    }
}
