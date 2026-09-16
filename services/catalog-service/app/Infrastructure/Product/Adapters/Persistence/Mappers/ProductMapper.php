<?php

namespace App\Infrastructure\Product\Adapters\Persistence\Mappers;

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ProductMapper
{
    public function toDomain(ProductModel $model): Product
    {
        return Product::reconstitute(
            ProductId::fromString($model->id),
            MerchantId::fromString($model->merchant_id),
            ProductName::fromString($model->name),
            $model->description,
            ProductStatus::from($model->status),
        );
    }

    public function toModel(Product $product, ?ProductModel $model = null): ProductModel
    {
        $model ??= new ProductModel;

        $model->id = $product->id()->toString();
        $model->merchant_id = $product->merchantId()->toString();
        $model->name = $product->name()->toString();
        $model->description = $product->description();
        $model->status = $product->status()->value;

        return $model;
    }
}
