<?php

namespace App\Infrastructure\Merchant\Adapters\Persistence\Repositories;

use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Infrastructure\Merchant\Adapters\Persistence\Mappers\MerchantMapper;
use App\Infrastructure\Merchant\Adapters\Persistence\Models\MerchantModel;

final class EloquentMerchantRepository implements IMerchantRepositoryPort
{
    public function __construct(private readonly MerchantMapper $mapper) {}

    public function save(Merchant $merchant): void
    {
        $model = MerchantModel::query()->find($merchant->id()->toString());

        $this->mapper->toModel($merchant, $model)->save();
    }

    public function get(MerchantId $id): Merchant
    {
        $model = MerchantModel::query()->find($id->toString());

        if ($model === null) {
            throw MerchantNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }
}
