<?php

namespace App\Application\Catalog;

use App\Application\Catalog\Ports\Inbound\ICatalogServicePort;
use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogHandler;
use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogQuery;
use App\Application\Catalog\ReadModels\ProductCatalog;

final class CatalogService implements ICatalogServicePort
{
    public function __construct(private readonly GetProductCatalogHandler $getProductCatalogHandler) {}

    public function getProductCatalog(GetProductCatalogQuery $query): ProductCatalog
    {
        return $this->getProductCatalogHandler->handle($query);
    }
}
