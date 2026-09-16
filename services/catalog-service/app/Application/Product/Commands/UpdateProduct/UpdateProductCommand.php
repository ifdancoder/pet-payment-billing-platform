<?php

namespace App\Application\Product\Commands\UpdateProduct;

final class UpdateProductCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $productId,
        public readonly string $name,
    ) {}
}
