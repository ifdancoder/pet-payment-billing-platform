<?php

namespace App\Domain\Merchant\ValueObjects;

use App\Domain\Merchant\Exceptions\InvalidMerchantName;

final class MerchantName
{
    private const MAX_LENGTH = 255;

    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidMerchantName::empty();
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidMerchantName::tooLong($trimmed);
        }

        return new self($trimmed);
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
