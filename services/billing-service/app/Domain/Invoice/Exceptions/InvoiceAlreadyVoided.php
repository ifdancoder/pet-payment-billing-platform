<?php

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use RuntimeException;

final class InvoiceAlreadyVoided extends RuntimeException
{
    public static function withId(InvoiceId $id): self
    {
        return new self("Invoice \"{$id->toString()}\" is already voided.");
    }
}
