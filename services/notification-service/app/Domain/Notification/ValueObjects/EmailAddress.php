<?php

namespace App\Domain\Notification\ValueObjects;

use App\Domain\Notification\Exceptions\InvalidEmailAddress;

final class EmailAddress
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmailAddress::forValue($value);
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
