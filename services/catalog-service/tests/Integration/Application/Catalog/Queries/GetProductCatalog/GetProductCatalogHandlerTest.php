<?php

use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogHandler;
use App\Application\Catalog\Queries\GetProductCatalog\GetProductCatalogQuery;
use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;

test('handle returns the product together with its prices', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));

    $catalog = app(GetProductCatalogHandler::class)->handle(new GetProductCatalogQuery($product->id()->toString()));

    expect($catalog->product->id()->equals($product->id()))->toBeTrue()
        ->and($catalog->prices)->toHaveCount(1);
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(GetProductCatalogHandler::class);

    $handler->handle(new GetProductCatalogQuery(ProductId::generate()->toString()));
})->throws(ProductNotFound::class);
