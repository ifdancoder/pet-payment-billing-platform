<?php

namespace App\Domain\Subscription\ValueObjects;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
}
