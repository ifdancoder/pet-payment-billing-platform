<?php

namespace App\Domain\Payment\ValueObjects;

use App\Domain\Payment\Exceptions\InvalidProviderReference;

/**
 * An opaque identifier from the payment provider (e.g. a Stripe
 * PaymentIntent id). Never a UUID we generate ourselves — this is
 * whatever the provider handed back, kept as-is for later lookups.
 */
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
