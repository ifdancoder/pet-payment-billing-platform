<?php

namespace App\Application\Product\Ports\Inbound;

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\Product;

interface IProductServicePort
{
    public function createProduct(CreateProductCommand $command): Product;

    /**
     * @return array<int, Product>
     */
    public function listProducts(ListProductsQuery $query): array;
}
