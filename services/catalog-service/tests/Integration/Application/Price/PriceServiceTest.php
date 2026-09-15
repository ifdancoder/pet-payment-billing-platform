<?php

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\PriceService;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\ProductService;

test('createPrice delegates to CreatePriceHandler and returns the created price', function () {
    $product = app(ProductService::class)->createProduct(new CreateProductCommand('Pro Plan'));
    $service = app(PriceService::class);

    $price = $service->createPrice(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        'monthly',
    ));

    expect($price->money()->amountMinorUnits())->toBe(1999);
});

test('getPrice delegates to GetPriceHandler and returns the matching price', function () {
    $product = app(ProductService::class)->createProduct(new CreateProductCommand('Pro Plan'));
    $service = app(PriceService::class);
    $created = $service->createPrice(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        'monthly',
    ));

    $found = $service->getPrice(new GetPriceQuery($created->id()->toString()));

    expect($found->id()->equals($created->id()))->toBeTrue();
});
