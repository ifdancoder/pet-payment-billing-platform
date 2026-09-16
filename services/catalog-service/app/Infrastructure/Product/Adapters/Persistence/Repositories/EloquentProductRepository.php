<?php

namespace App\Infrastructure\Product\Adapters\Persistence\Repositories;

use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentProductRepository implements IProductRepositoryPort
{
    public function __construct(private readonly ProductMapper $mapper) {}

    public function save(Product $product): void
    {
        $model = ProductModel::query()->find($product->id()->toString());

        $this->mapper->toModel($product, $model)->save();
    }

    public function get(ProductId $id, MerchantId $merchantId): Product
    {
        $model = ProductModel::query()
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw ProductNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function all(MerchantId $merchantId): array
    {
        return ProductModel::query()
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (ProductModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
