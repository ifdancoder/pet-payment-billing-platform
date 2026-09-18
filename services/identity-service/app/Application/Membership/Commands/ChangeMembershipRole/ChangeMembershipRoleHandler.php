<?php

namespace App\Application\Membership\Commands\ChangeMembershipRole;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;

final class ChangeMembershipRoleHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository) {}

    public function handle(ChangeMembershipRoleCommand $command): Membership
    {
        $membership = $this->repository->get(MembershipId::fromString($command->membershipId));

        $membership->changeRole(Role::from($command->role));

        $this->repository->save($membership);

        return $membership;
    }
}
