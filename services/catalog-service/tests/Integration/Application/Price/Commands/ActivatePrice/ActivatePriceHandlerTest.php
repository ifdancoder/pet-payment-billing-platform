<?php

use App\Application\Price\Commands\ActivatePrice\ActivatePriceCommand;
use App\Application\Price\Commands\ActivatePrice\ActivatePriceHandler;
use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceCommand;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceHandler;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Domain\Price\Events\PriceActivated;
use App\Domain\Price\Exceptions\PriceAlreadyActive;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use Illuminate\Support\Facades\Log;

test('handle activates an existing inactive price', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    app(DeactivatePriceHandler::class)->handle(new DeactivatePriceCommand($price->id()->toString()));
    $handler = app(ActivatePriceHandler::class);

    $activated = $handler->handle(new ActivatePriceCommand($price->id()->toString()));

    expect($activated->status())->toBe(PriceStatus::Active);
    $persisted = app(IPriceRepositoryPort::class)->get($price->id());
    expect($persisted->status())->toBe(PriceStatus::Active);
});

test('handle throws PriceNotFound when the price does not exist', function () {
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand(PriceId::generate()->toString()));
})->throws(PriceNotFound::class);

test('handle throws PriceAlreadyActive when the price is already active', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand($price->id()->toString()));
})->throws(PriceAlreadyActive::class);

test('handle publishes a PriceActivated event', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    app(DeactivatePriceHandler::class)->handle(new DeactivatePriceCommand($price->id()->toString()));
    Log::spy();
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand($price->id()->toString()));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, PriceActivated::class));
});
