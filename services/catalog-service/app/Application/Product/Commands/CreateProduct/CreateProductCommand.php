<?php

namespace App\Application\Product\Commands\CreateProduct;

final class CreateProductCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
}
