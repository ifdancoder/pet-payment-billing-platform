<?php

namespace App\Application\Product\Commands\ArchiveProduct;

final class ArchiveProductCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $productId,
    ) {}
}
