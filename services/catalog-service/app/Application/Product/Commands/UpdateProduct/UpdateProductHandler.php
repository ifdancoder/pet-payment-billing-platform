<?php

namespace App\Application\Product\Commands\UpdateProduct;

use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Shared\Domain\ValueObjects\MerchantId;

final class UpdateProductHandler
{
    public function __construct(private readonly IProductRepositoryPort $repository) {}

    public function handle(UpdateProductCommand $command): Product
    {
        $product = $this->repository->get(
            ProductId::fromString($command->productId),
            MerchantId::fromString($command->merchantId),
        );

        $product->rename(ProductName::fromString($command->name));

        $this->repository->save($product);

        return $product;
    }
}
