<?php

namespace App\Application\Membership\Commands\AddMembership;

final class AddMembershipCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $merchantId,
        public readonly int $role,
    ) {}
}
