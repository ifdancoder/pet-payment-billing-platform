<?php

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\Exceptions\InvalidPriceId;
use Ramsey\Uuid\Uuid;

final class PriceId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        if (! Uuid::isValid($value)) {
            throw InvalidPriceId::forValue($value);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
