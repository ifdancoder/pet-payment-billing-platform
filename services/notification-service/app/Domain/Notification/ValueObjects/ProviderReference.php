<?php

namespace App\Domain\Notification\ValueObjects;

use App\Domain\Notification\Exceptions\InvalidProviderReference;

/** Opaque identifier returned by the delivery provider. */
final class ProviderReference
{
    private function __construct(private readonly string $value) {}

    public static function of(string $value): self
    {
        if ($value === '') {
            throw InvalidProviderReference::mustNotBeEmpty();
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
