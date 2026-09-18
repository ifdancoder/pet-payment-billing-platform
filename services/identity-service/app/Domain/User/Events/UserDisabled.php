<?php

namespace App\Domain\User\Events;

use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final class UserDisabled
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly UserId $userId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
