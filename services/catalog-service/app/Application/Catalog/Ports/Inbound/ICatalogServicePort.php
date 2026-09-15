<?php

namespace App\Application\Catalog\Ports\Inbound;

use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogQuery;
use App\Application\Catalog\ReadModels\ProductCatalog;

interface ICatalogServicePort
{
    public function getProductCatalog(GetProductCatalogQuery $query): ProductCatalog;
}
