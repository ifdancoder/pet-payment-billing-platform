<?php

namespace App\Infrastructure\Membership\Adapters\Persistence\Mappers;

use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Membership\Adapters\Persistence\Models\MembershipModel;

final class MembershipMapper
{
    public function toDomain(MembershipModel $model): Membership
    {
        return Membership::reconstitute(
            MembershipId::fromString($model->id),
            UserId::fromString($model->user_id),
            MerchantId::fromString($model->merchant_id),
            Role::from($model->role),
        );
    }

    public function toModel(Membership $membership, ?MembershipModel $model = null): MembershipModel
    {
        $model ??= new MembershipModel;

        $model->id = $membership->id()->toString();
        $model->user_id = $membership->userId()->toString();
        $model->merchant_id = $membership->merchantId()->toString();
        $model->role = $membership->role()->value;

        return $model;
    }
}
