<?php

namespace App\Domain\Product\Exceptions;

use App\Domain\Product\ValueObjects\ProductId;
use RuntimeException;

final class ArchivedProductCannotBeModified extends RuntimeException
{
    public static function withId(ProductId $id): self
    {
        return new self("Product \"{$id->toString()}\" is archived and cannot be modified.");
    }
}
