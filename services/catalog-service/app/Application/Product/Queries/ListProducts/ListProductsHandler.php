<?php

namespace App\Application\Product\Queries\ListProducts;

use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Product;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ListProductsHandler
{
    public function __construct(private readonly IProductRepositoryPort $repository) {}

    /**
     * @return array<int, Product>
     */
    public function handle(ListProductsQuery $query): array
    {
        return $this->repository->all(MerchantId::fromString($query->merchantId));
    }
}
