<?php

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Price\Exceptions\InvalidPrice;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;

test('handle persists a new recurring price for an existing product', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));

    $persisted = app(IPriceRepositoryPort::class)->get($price->id());
    expect($persisted->productId()->equals($product->id()))->toBeTrue()
        ->and($persisted->money()->amountMinorUnits())->toBe(1999)
        ->and($persisted->billingPeriod()->interval())->toBe(BillingInterval::Month);
});

test('handle persists a new one-time price for an existing product', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand(
        $product->id()->toString(),
        4999,
        'USD',
        PriceType::OneTime->value,
    ));

    $persisted = app(IPriceRepositoryPort::class)->get($price->id());
    expect($persisted->type())->toBe(PriceType::OneTime)
        ->and($persisted->billingPeriod())->toBeNull();
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(CreatePriceHandler::class);

    $handler->handle(new CreatePriceCommand(
        ProductId::generate()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));
})->throws(ProductNotFound::class);

test('handle throws InvalidPrice when a recurring price is requested without a billing period', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $handler->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
    ));
})->throws(InvalidPrice::class);
