<?php

namespace App\Domain\Price\Exceptions;

use App\Domain\Price\ValueObjects\PriceId;
use RuntimeException;

final class PriceNotFound extends RuntimeException
{
    public static function withId(PriceId $id): self
    {
        return new self("Price \"{$id->toString()}\" was not found.");
    }
}
