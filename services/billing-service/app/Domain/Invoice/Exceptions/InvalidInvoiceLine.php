<?php

namespace App\Domain\Invoice\Exceptions;

use InvalidArgumentException;

final class InvalidInvoiceLine extends InvalidArgumentException
{
    public static function quantityMustBeAtLeastOne(int $quantity): self
    {
        return new self("{$quantity} is not a valid invoice line quantity: it must be at least 1.");
    }
}
