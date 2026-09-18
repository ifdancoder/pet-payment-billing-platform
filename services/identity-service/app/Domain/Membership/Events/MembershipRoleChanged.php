<?php

namespace App\Domain\Membership\Events;

use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use DateTimeImmutable;

final class MembershipRoleChanged
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly MembershipId $membershipId,
        public readonly Role $previousRole,
        public readonly Role $newRole,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
