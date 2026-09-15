<?php

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\UpdateProduct\UpdateProductCommand;
use App\Application\Product\ProductService;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\ValueObjects\ProductStatus;

test('createProduct delegates to CreateProductHandler and returns the created product', function () {
    $service = app(ProductService::class);

    $product = $service->createProduct(new CreateProductCommand('Pro Plan'));

    expect($product->name()->toString())->toBe('Pro Plan');
});

test('listProducts delegates to ListProductsHandler and returns every product', function () {
    $service = app(ProductService::class);
    $service->createProduct(new CreateProductCommand('Pro Plan'));
    $service->createProduct(new CreateProductCommand('Team Plan'));

    $products = $service->listProducts(new ListProductsQuery);

    expect($products)->toHaveCount(2);
});

test('updateProduct delegates to UpdateProductHandler and returns the renamed product', function () {
    $service = app(ProductService::class);
    $product = $service->createProduct(new CreateProductCommand('Pro Plan'));

    $updated = $service->updateProduct(new UpdateProductCommand($product->id()->toString(), 'Pro Plan v2'));

    expect($updated->name()->toString())->toBe('Pro Plan v2');
});

test('archiveProduct delegates to ArchiveProductHandler and returns the archived product', function () {
    $service = app(ProductService::class);
    $product = $service->createProduct(new CreateProductCommand('Pro Plan'));

    $archived = $service->archiveProduct(new ArchiveProductCommand($product->id()->toString()));

    expect($archived->status())->toBe(ProductStatus::Archived);
});
