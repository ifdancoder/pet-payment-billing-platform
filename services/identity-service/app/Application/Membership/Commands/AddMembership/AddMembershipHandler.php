<?php

namespace App\Application\Membership\Commands\AddMembership;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Exceptions\UserAlreadyAMember;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\UniqueConstraintViolationException;

final class AddMembershipHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository) {}

    /**
     * @throws UserAlreadyAMember
     */
    public function handle(AddMembershipCommand $command): Membership
    {
        $userId = UserId::fromString($command->userId);
        $merchantId = MerchantId::fromString($command->merchantId);

        if ($this->repository->findByUserAndMerchant($userId, $merchantId) !== null) {
            throw UserAlreadyAMember::of($userId, $merchantId);
        }

        $membership = Membership::create(MembershipId::generate(), $userId, $merchantId, Role::from($command->role));

        try {
            $this->repository->save($membership);
        } catch (UniqueConstraintViolationException) {
            // Lost a race with a concurrent add for the same user/merchant
            // pair — the winner's membership is the truth.
            throw UserAlreadyAMember::of($userId, $merchantId);
        }

        return $membership;
    }
}
