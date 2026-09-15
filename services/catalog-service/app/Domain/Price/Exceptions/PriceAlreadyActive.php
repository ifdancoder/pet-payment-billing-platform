<?php

namespace App\Domain\Price\Exceptions;

use App\Domain\Price\ValueObjects\PriceId;
use RuntimeException;

final class PriceAlreadyActive extends RuntimeException
{
    public static function withId(PriceId $id): self
    {
        return new self("Price \"{$id->toString()}\" is already active.");
    }
}
