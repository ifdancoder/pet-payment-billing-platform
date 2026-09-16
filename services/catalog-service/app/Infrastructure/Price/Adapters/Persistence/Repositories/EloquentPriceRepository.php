<?php

namespace App\Infrastructure\Price\Adapters\Persistence\Repositories;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentPriceRepository implements IPriceRepositoryPort
{
    public function __construct(private readonly PriceMapper $mapper) {}

    public function save(Price $price): void
    {
        $model = PriceModel::query()->find($price->id()->toString());

        $this->mapper->toModel($price, $model)->save();
    }

    public function get(PriceId $id, MerchantId $merchantId): Price
    {
        $model = PriceModel::query()
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw PriceNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }
}
