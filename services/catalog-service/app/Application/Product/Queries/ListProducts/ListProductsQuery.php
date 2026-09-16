<?php

namespace App\Application\Product\Queries\ListProducts;

final class ListProductsQuery
{
    public function __construct(public readonly string $merchantId) {}
}
