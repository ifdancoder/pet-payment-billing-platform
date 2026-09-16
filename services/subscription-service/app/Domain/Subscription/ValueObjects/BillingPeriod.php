<?php

namespace App\Domain\Subscription\ValueObjects;

use App\Domain\Subscription\Exceptions\InvalidBillingPeriod;

final class BillingPeriod
{
    private function __construct(
        private readonly BillingInterval $interval,
        private readonly int $count,
    ) {}

    public static function of(BillingInterval $interval, int $count): self
    {
        if ($count < 1) {
            throw InvalidBillingPeriod::countMustBeAtLeastOne($count);
        }

        return new self($interval, $count);
    }

    public function interval(): BillingInterval
    {
        return $this->interval;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function equals(self $other): bool
    {
        return $this->interval === $other->interval
            && $this->count === $other->count;
    }
}
