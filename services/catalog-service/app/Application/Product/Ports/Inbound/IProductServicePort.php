<?php

namespace App\Application\Product\Ports\Inbound;

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Domain\Product\Product;

interface IProductServicePort
{
    public function createProduct(CreateProductCommand $command): Product;
}
