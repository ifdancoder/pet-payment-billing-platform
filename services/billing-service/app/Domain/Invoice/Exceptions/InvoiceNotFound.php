<?php

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use RuntimeException;

final class InvoiceNotFound extends RuntimeException
{
    public static function withId(InvoiceId $id): self
    {
        return new self("Invoice \"{$id->toString()}\" was not found.");
    }
}
