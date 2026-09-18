<?php

namespace App\Domain\User\Events;

use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final class UserRegistered
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly UserId $userId,
        public readonly Email $email,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
