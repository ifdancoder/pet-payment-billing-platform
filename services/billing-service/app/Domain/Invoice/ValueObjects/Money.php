<?php

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\Exceptions\CurrencyMismatch;
use App\Domain\Invoice\Exceptions\InvalidMoney;

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

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }

        return self::of($this->amountMinorUnits + $other->amountMinorUnits, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return self::of($this->amountMinorUnits * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountMinorUnits === $other->amountMinorUnits
            && $this->currency === $other->currency;
    }
}
