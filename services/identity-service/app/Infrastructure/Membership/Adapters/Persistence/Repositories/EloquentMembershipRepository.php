<?php

namespace App\Infrastructure\Membership\Adapters\Persistence\Repositories;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Exceptions\MembershipNotFound;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Membership\Adapters\Persistence\Mappers\MembershipMapper;
use App\Infrastructure\Membership\Adapters\Persistence\Models\MembershipModel;

final class EloquentMembershipRepository implements IMembershipRepositoryPort
{
    public function __construct(private readonly MembershipMapper $mapper) {}

    public function save(Membership $membership): void
    {
        $model = MembershipModel::query()->find($membership->id()->toString());

        $this->mapper->toModel($membership, $model)->save();
    }

    public function delete(MembershipId $id): void
    {
        MembershipModel::query()->where('id', $id->toString())->delete();
    }

    public function get(MembershipId $id): Membership
    {
        $model = MembershipModel::query()->find($id->toString());

        if ($model === null) {
            throw MembershipNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function getForMerchant(MembershipId $id, MerchantId $merchantId): Membership
    {
        $model = MembershipModel::query()
            ->whereKey($id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw MembershipNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function countOwnersForUpdate(MerchantId $merchantId): int
    {
        return MembershipModel::query()
            ->where('merchant_id', $merchantId->toString())
            ->where('role', Role::Owner->value)
            ->lockForUpdate()
            ->count();
    }

    public function findByUserAndMerchant(UserId $userId, MerchantId $merchantId): ?Membership
    {
        $model = MembershipModel::query()
            ->where('user_id', $userId->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listByUser(UserId $userId): array
    {
        return MembershipModel::query()
            ->where('user_id', $userId->toString())
            ->get()
            ->map(fn (MembershipModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function listByMerchant(MerchantId $merchantId): array
    {
        return MembershipModel::query()
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (MembershipModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
