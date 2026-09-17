<?php

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\Currency;
use RuntimeException;

final class CurrencyMismatch extends RuntimeException
{
    public static function between(Currency $a, Currency $b): self
    {
        return new self("Cannot combine {$a->value} with {$b->value}.");
    }
}
