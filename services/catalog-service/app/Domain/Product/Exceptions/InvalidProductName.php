<?php

namespace App\Domain\Product\Exceptions;

use InvalidArgumentException;

final class InvalidProductName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Product name must not be empty.');
    }

    public static function tooLong(string $value): self
    {
        return new self(sprintf('Product name must not be longer than 255 characters, got %d.', mb_strlen($value)));
    }
}
