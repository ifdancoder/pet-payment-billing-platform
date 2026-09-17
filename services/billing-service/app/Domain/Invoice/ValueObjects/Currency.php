<?php

namespace App\Domain\Invoice\ValueObjects;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
}
