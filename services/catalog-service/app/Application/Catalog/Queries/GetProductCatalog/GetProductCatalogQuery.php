<?php

namespace App\Application\Catalog\Queries\GetProductCatalog;

final class GetProductCatalogQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $productId,
    ) {}
}
