<?php

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\Exceptions\InvalidBillingPeriod;
use DateTimeImmutable;

final class BillingPeriod
{
    private function __construct(
        private readonly DateTimeImmutable $start,
        private readonly DateTimeImmutable $end,
    ) {}

    public static function of(DateTimeImmutable $start, DateTimeImmutable $end): self
    {
        if ($end <= $start) {
            throw InvalidBillingPeriod::endMustBeAfterStart();
        }

        return new self($start, $end);
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    public function equals(self $other): bool
    {
        return $this->start == $other->start
            && $this->end == $other->end;
    }
}
