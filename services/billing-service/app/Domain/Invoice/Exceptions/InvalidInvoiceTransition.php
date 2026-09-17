<?php

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceStatus;
use RuntimeException;

final class InvalidInvoiceTransition extends RuntimeException
{
    public static function forAction(InvoiceId $id, string $action, InvoiceStatus $currentStatus): self
    {
        return new self("Invoice \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
