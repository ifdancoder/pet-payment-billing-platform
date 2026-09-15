<?php

namespace App\Domain\Price\ValueObjects;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
}
