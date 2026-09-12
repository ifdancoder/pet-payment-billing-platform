<?php

namespace App\Domain\Customer\ValueObjects;

use App\Domain\Customer\Exceptions\InvalidEmail;

final class Email
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::forValue($value);
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
