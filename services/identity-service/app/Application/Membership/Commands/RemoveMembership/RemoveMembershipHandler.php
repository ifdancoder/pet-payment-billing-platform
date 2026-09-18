<?php

namespace App\Application\Membership\Commands\RemoveMembership;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\ValueObjects\MembershipId;

final class RemoveMembershipHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository) {}

    /**
     * @throws MembershipNotFound
     */
    public function handle(RemoveMembershipCommand $command): void
    {
        $id = MembershipId::fromString($command->membershipId);

        // get() first so removing an already-gone membership throws
        // MembershipNotFound instead of delete() silently affecting zero
        // rows.
        $this->repository->get($id);

        $this->repository->delete($id);
    }
}
