<?php

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Queries\GetPrice\GetPriceHandler;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;

test('handle returns the matching price', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));

    $found = app(GetPriceHandler::class)->handle(new GetPriceQuery($price->id()->toString()));

    expect($found->id()->equals($price->id()))->toBeTrue();
});

test('handle throws PriceNotFound when no price matches', function () {
    $handler = app(GetPriceHandler::class);

    $handler->handle(new GetPriceQuery(PriceId::generate()->toString()));
})->throws(PriceNotFound::class);
