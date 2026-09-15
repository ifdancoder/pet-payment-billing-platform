<?php

namespace App\Infrastructure\Price\Adapters\Persistence\Repositories;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;

final class EloquentPriceRepository implements IPriceRepositoryPort
{
    public function __construct(private readonly PriceMapper $mapper) {}

    public function save(Price $price): void
    {
        $model = PriceModel::query()->find($price->id()->toString());

        $this->mapper->toModel($price, $model)->save();
    }

    public function get(PriceId $id): Price
    {
        $model = PriceModel::query()->find($id->toString());

        if ($model === null) {
            throw PriceNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }
}
