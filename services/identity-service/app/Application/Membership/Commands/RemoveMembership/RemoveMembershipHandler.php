<?php

namespace App\Application\Membership\Commands\RemoveMembership;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\Exceptions\LastOwner;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class RemoveMembershipHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository, private readonly ITransactionManagerPort $transactions) {}

    /**
     * @throws MembershipNotFound
     */
    public function handle(RemoveMembershipCommand $command): void
    {
        $this->transactions->run(function () use ($command): void {
            $id = MembershipId::fromString($command->membershipId);
            $merchantId = MerchantId::fromString($command->merchantId);
            $membership = $this->repository->getForMerchant($id, $merchantId);
            if ($membership->role() === Role::Owner && $this->repository->countOwnersForUpdate($merchantId) <= 1) {
                throw new LastOwner;
            }
            $this->repository->delete($id);
        });
    }
}
