<?php

namespace App\Domain\Merchant\Exceptions;

use InvalidArgumentException;

final class InvalidMerchantName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Merchant name must not be empty.');
    }

    public static function tooLong(string $value): self
    {
        return new self(sprintf('Merchant name must not be longer than 255 characters, got %d.', mb_strlen($value)));
    }
}
