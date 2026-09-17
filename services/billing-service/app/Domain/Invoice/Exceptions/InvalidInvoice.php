<?php

namespace App\Domain\Invoice\Exceptions;

use InvalidArgumentException;

final class InvalidInvoice extends InvalidArgumentException
{
    public static function mustHaveAtLeastOneLine(): self
    {
        return new self('An invoice must have at least one line.');
    }
}
