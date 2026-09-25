<?php

namespace App\Shared\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidMerchantId extends InvalidArgumentException
{
    public static function withValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid merchant id.', $value));
    }
}
