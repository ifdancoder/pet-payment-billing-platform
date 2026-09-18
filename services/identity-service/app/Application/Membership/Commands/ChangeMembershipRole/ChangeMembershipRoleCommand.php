<?php

namespace App\Application\Membership\Commands\ChangeMembershipRole;

final class ChangeMembershipRoleCommand
{
    public function __construct(
        public readonly string $membershipId,
        public readonly int $role,
    ) {}
}
