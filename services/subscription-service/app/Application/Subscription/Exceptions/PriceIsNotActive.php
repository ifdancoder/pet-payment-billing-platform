<?php

namespace App\Application\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\PriceId;
use RuntimeException;

final class PriceIsNotActive extends RuntimeException
{
    public static function withId(PriceId $id): self
    {
        return new self("Price \"{$id->toString()}\" is not active and cannot back a new subscription.");
    }
}
