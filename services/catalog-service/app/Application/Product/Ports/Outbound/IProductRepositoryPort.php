<?php

namespace App\Application\Product\Ports\Outbound;

use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface IProductRepositoryPort
{
    public function save(Product $product): void;

    /**
     * @throws ProductNotFound
     */
    public function get(ProductId $id, MerchantId $merchantId): Product;

    /**
     * @return array<int, Product>
     */
    public function all(MerchantId $merchantId): array;
}
