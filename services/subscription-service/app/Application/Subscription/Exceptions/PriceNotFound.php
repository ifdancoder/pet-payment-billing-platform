<?php

namespace App\Application\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\PriceId;
use RuntimeException;

final class PriceNotFound extends RuntimeException
{
    public static function withId(PriceId $id): self
    {
        return new self("Price \"{$id->toString()}\" was not found.");
    }
}
