<?php

namespace App\Application\Catalog\ReadModels;

use App\Domain\Price\Price;
use App\Domain\Product\Product;

final class ProductCatalog
{
    /**
     * @param  array<int, Price>  $prices
     */
    public function __construct(
        public readonly Product $product,
        public readonly array $prices,
    ) {}
}
