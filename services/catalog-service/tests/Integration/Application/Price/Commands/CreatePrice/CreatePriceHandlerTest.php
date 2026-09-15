<?php

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;

test('handle persists a new price for an existing product', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        'monthly',
    ));

    $persisted = app(IPriceRepositoryPort::class)->get($price->id());
    expect($persisted->productId()->equals($product->id()))->toBeTrue()
        ->and($persisted->money()->amountMinorUnits())->toBe(1999);
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(CreatePriceHandler::class);

    $handler->handle(new CreatePriceCommand(
        ProductId::generate()->toString(),
        1999,
        'USD',
        'monthly',
    ));
})->throws(ProductNotFound::class);
