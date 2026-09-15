<?php

namespace App\Domain\Price\ValueObjects;

use App\Domain\Price\Exceptions\InvalidMoney;

final class Money
{
    private function __construct(
        private readonly int $amountMinorUnits,
        private readonly Currency $currency,
    ) {}

    public static function of(int $amountMinorUnits, Currency $currency): self
    {
        if ($amountMinorUnits < 0) {
            throw InvalidMoney::negativeAmount($amountMinorUnits);
        }

        return new self($amountMinorUnits, $currency);
    }

    public function amountMinorUnits(): int
    {
        return $this->amountMinorUnits;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amountMinorUnits === $other->amountMinorUnits
            && $this->currency === $other->currency;
    }
}
