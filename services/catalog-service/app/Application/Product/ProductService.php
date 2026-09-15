<?php

namespace App\Application\Product;

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Inbound\IProductServicePort;
use App\Domain\Product\Product;

final class ProductService implements IProductServicePort
{
    public function __construct(private readonly CreateProductHandler $createProductHandler) {}

    public function createProduct(CreateProductCommand $command): Product
    {
        return $this->createProductHandler->handle($command);
    }
}
