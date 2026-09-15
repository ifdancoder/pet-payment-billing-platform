<?php

namespace App\Application\Catalog\Queries\GetProductCatalog;

use App\Application\Catalog\Ports\Outbound\ICatalogQueryPort;
use App\Application\Catalog\ReadModels\ProductCatalog;
use App\Domain\Product\ValueObjects\ProductId;

final class GetProductCatalogHandler
{
    public function __construct(private readonly ICatalogQueryPort $catalogQuery) {}

    public function handle(GetProductCatalogQuery $query): ProductCatalog
    {
        return $this->catalogQuery->getProductCatalog(ProductId::fromString($query->productId));
    }
}
