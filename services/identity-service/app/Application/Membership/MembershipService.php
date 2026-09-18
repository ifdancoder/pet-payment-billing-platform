<?php

namespace App\Application\Membership;

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\AddMembership\AddMembershipHandler;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleHandler;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipCommand;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipHandler;
use App\Application\Membership\Ports\Inbound\IMembershipServicePort;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsHandler;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsQuery;
use App\Domain\Membership\Membership;

final class MembershipService implements IMembershipServicePort
{
    public function __construct(
        private readonly AddMembershipHandler $addMembershipHandler,
        private readonly ChangeMembershipRoleHandler $changeMembershipRoleHandler,
        private readonly RemoveMembershipHandler $removeMembershipHandler,
        private readonly ListMembershipsHandler $listMembershipsHandler,
    ) {}

    public function addMembership(AddMembershipCommand $command): Membership
    {
        return $this->addMembershipHandler->handle($command);
    }

    public function changeMembershipRole(ChangeMembershipRoleCommand $command): Membership
    {
        return $this->changeMembershipRoleHandler->handle($command);
    }

    public function removeMembership(RemoveMembershipCommand $command): void
    {
        $this->removeMembershipHandler->handle($command);
    }

    public function listMemberships(ListMembershipsQuery $query): array
    {
        return $this->listMembershipsHandler->handle($query);
    }
}
