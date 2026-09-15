<?php

namespace App\Infrastructure\Catalog\Adapters\Persistence\Queries;

use App\Application\Catalog\Ports\Outbound\ICatalogQueryPort;
use App\Application\Catalog\ReadModels\ProductCatalog;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;

final class EloquentCatalogQuery implements ICatalogQueryPort
{
    public function __construct(
        private readonly ProductMapper $productMapper,
        private readonly PriceMapper $priceMapper,
    ) {}

    public function getProductCatalog(ProductId $productId): ProductCatalog
    {
        $productModel = ProductModel::query()->find($productId->toString());

        if ($productModel === null) {
            throw ProductNotFound::withId($productId);
        }

        $prices = PriceModel::query()
            ->where('product_id', $productId->toString())
            ->get()
            ->map(fn (PriceModel $model) => $this->priceMapper->toDomain($model))
            ->all();

        return new ProductCatalog($this->productMapper->toDomain($productModel), $prices);
    }
}
