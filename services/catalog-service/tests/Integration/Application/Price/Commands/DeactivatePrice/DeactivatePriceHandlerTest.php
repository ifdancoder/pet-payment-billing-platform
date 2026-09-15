<?php

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceCommand;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceHandler;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Price\Exceptions\PriceAlreadyInactive;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;

test('handle deactivates an existing active price', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(DeactivatePriceHandler::class);

    $deactivated = $handler->handle(new DeactivatePriceCommand($price->id()->toString()));

    expect($deactivated->status())->toBe(PriceStatus::Inactive);
    $persisted = app(IPriceRepositoryPort::class)->get($price->id());
    expect($persisted->status())->toBe(PriceStatus::Inactive);
});

test('handle throws PriceNotFound when the price does not exist', function () {
    $handler = app(DeactivatePriceHandler::class);

    $handler->handle(new DeactivatePriceCommand(PriceId::generate()->toString()));
})->throws(PriceNotFound::class);

test('handle throws PriceAlreadyInactive when the price is already inactive', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(DeactivatePriceHandler::class);
    $handler->handle(new DeactivatePriceCommand($price->id()->toString()));

    $handler->handle(new DeactivatePriceCommand($price->id()->toString()));
})->throws(PriceAlreadyInactive::class);
