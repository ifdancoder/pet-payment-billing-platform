<?php

namespace App\Application\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\PriceId;
use RuntimeException;

final class PriceIsNotRecurring extends RuntimeException
{
    public static function withId(PriceId $id): self
    {
        return new self("Price \"{$id->toString()}\" is not a recurring price and cannot back a subscription.");
    }
}
