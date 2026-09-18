<?php

namespace App\Domain\Membership\Events;

use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final class MembershipAdded
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly MembershipId $membershipId,
        public readonly UserId $userId,
        public readonly MerchantId $merchantId,
        public readonly Role $role,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
