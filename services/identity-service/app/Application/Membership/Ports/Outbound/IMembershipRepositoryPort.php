<?php

namespace App\Application\Membership\Ports\Outbound;

use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;

interface IMembershipRepositoryPort
{
    public function save(Membership $membership): void;

    public function delete(MembershipId $id): void;

    /**
     * @throws MembershipNotFound
     */
    public function get(MembershipId $id): Membership;

    public function findByUserAndMerchant(UserId $userId, MerchantId $merchantId): ?Membership;

    /**
     * @return array<int, Membership>
     */
    public function listByUser(UserId $userId): array;

    /**
     * @return array<int, Membership>
     */
    public function listByMerchant(MerchantId $merchantId): array;
}
