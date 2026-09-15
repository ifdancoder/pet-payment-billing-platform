<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\ProductService;

test('createProduct delegates to CreateProductHandler and returns the created product', function () {
    $service = app(ProductService::class);

    $product = $service->createProduct(new CreateProductCommand('Pro Plan'));

    expect($product->name()->toString())->toBe('Pro Plan');
});
