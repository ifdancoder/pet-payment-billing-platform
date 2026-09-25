<?php

namespace App\Application\Membership\Commands\RemoveMembership;

final class RemoveMembershipCommand
{
    public function __construct(public readonly string $merchantId, public readonly string $membershipId) {}
}
