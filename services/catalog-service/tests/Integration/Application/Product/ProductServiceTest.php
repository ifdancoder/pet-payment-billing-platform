<?php

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\UpdateProduct\UpdateProductCommand;
use App\Application\Product\ProductService;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Shared\Domain\ValueObjects\MerchantId;

test('createProduct delegates to CreateProductHandler and returns the created product', function () {
    $service = app(ProductService::class);

    $product = $service->createProduct(new CreateProductCommand(MerchantId::generate()->toString(), 'Pro Plan'));

    expect($product->name()->toString())->toBe('Pro Plan');
});

test('listProducts delegates to ListProductsHandler and returns every product for the given merchant', function () {
    $service = app(ProductService::class);
    $merchantId = MerchantId::generate();
    $service->createProduct(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $service->createProduct(new CreateProductCommand($merchantId->toString(), 'Team Plan'));

    $products = $service->listProducts(new ListProductsQuery($merchantId->toString()));

    expect($products)->toHaveCount(2);
});

test('updateProduct delegates to UpdateProductHandler and returns the renamed product', function () {
    $service = app(ProductService::class);
    $merchantId = MerchantId::generate();
    $product = $service->createProduct(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));

    $updated = $service->updateProduct(new UpdateProductCommand($merchantId->toString(), $product->id()->toString(), 'Pro Plan v2'));

    expect($updated->name()->toString())->toBe('Pro Plan v2');
});

test('archiveProduct delegates to ArchiveProductHandler and returns the archived product', function () {
    $service = app(ProductService::class);
    $merchantId = MerchantId::generate();
    $product = $service->createProduct(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));

    $archived = $service->archiveProduct(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));

    expect($archived->status())->toBe(ProductStatus::Archived);
});
