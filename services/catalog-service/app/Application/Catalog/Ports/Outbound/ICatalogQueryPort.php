<?php

namespace App\Application\Catalog\Ports\Outbound;

use App\Application\Catalog\ReadModels\ProductCatalog;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;

interface ICatalogQueryPort
{
    /**
     * @throws ProductNotFound
     */
    public function getProductCatalog(ProductId $productId): ProductCatalog;
}
