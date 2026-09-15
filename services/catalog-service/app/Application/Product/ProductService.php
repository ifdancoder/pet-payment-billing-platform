<?php

namespace App\Application\Product;

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\ArchiveProduct\ArchiveProductHandler;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Commands\UpdateProduct\UpdateProductCommand;
use App\Application\Product\Commands\UpdateProduct\UpdateProductHandler;
use App\Application\Product\Ports\Inbound\IProductServicePort;
use App\Application\Product\Queries\ListProducts\ListProductsHandler;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\Product;

final class ProductService implements IProductServicePort
{
    public function __construct(
        private readonly CreateProductHandler $createProductHandler,
        private readonly ListProductsHandler $listProductsHandler,
        private readonly UpdateProductHandler $updateProductHandler,
        private readonly ArchiveProductHandler $archiveProductHandler,
    ) {}

    public function createProduct(CreateProductCommand $command): Product
    {
        return $this->createProductHandler->handle($command);
    }

    public function listProducts(ListProductsQuery $query): array
    {
        return $this->listProductsHandler->handle($query);
    }

    public function updateProduct(UpdateProductCommand $command): Product
    {
        return $this->updateProductHandler->handle($command);
    }

    public function archiveProduct(ArchiveProductCommand $command): Product
    {
        return $this->archiveProductHandler->handle($command);
    }
}
