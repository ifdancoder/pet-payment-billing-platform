<?php

namespace App\Shared\Domain\ValueObjects;

use App\Shared\Domain\Exceptions\InvalidMerchantId;
use Ramsey\Uuid\Uuid;

final readonly class MerchantId
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (! Uuid::isValid($value)) {
            throw InvalidMerchantId::withValue($value);
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
