<?php

namespace App\Domain\Product\ValueObjects;

enum ProductStatus: int
{
    case Active = 1;
    case Archived = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Archived => 'archived',
        };
    }
}
