<?php

namespace App\Application\Catalog\Ports\Outbound;

use App\Application\Catalog\ReadModels\ProductCatalog;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface ICatalogQueryPort
{
    /**
     * @throws ProductNotFound
     */
    public function getProductCatalog(ProductId $productId, MerchantId $merchantId): ProductCatalog;
}
