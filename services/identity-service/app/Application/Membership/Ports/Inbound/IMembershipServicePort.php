<?php

namespace App\Application\Membership\Ports\Inbound;

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipCommand;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsQuery;
use App\Domain\Membership\Membership;

interface IMembershipServicePort
{
    public function addMembership(AddMembershipCommand $command): Membership;

    public function changeMembershipRole(ChangeMembershipRoleCommand $command): Membership;

    public function removeMembership(RemoveMembershipCommand $command): void;

    /**
     * @return array<int, Membership>
     */
    public function listMemberships(ListMembershipsQuery $query): array;
}
