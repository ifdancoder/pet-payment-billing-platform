<?php

namespace App\Application\Membership\Commands\ChangeMembershipRole;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Membership;
use App\Domain\Membership\Exceptions\LastOwner;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class ChangeMembershipRoleHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository, private readonly ITransactionManagerPort $transactions) {}

    public function handle(ChangeMembershipRoleCommand $command): Membership
    {
        return $this->transactions->run(function () use ($command): Membership {
            $merchantId = MerchantId::fromString($command->merchantId);
            $membership = $this->repository->getForMerchant(MembershipId::fromString($command->membershipId), $merchantId);
            $newRole = Role::from($command->role);
            if ($membership->role() === Role::Owner && $newRole !== Role::Owner
                && $this->repository->countOwnersForUpdate($merchantId) <= 1) {
                throw new LastOwner;
            }
            $membership->changeRole($newRole);
            $this->repository->save($membership);
            return $membership;
        });
    }
}
