<?php

namespace App\Infrastructure\Product\Adapters\Persistence\Repositories;

use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;

final class EloquentProductRepository implements IProductRepositoryPort
{
    public function __construct(private readonly ProductMapper $mapper) {}

    public function save(Product $product): void
    {
        $model = ProductModel::query()->find($product->id()->toString());

        $this->mapper->toModel($product, $model)->save();
    }

    public function get(ProductId $id): Product
    {
        $model = ProductModel::query()->find($id->toString());

        if ($model === null) {
            throw ProductNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }
}
