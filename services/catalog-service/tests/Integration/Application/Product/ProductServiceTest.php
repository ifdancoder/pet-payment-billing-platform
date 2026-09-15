<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\ProductService;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;

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
