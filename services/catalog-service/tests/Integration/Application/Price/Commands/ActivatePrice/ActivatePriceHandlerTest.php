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
use App\Domain\Price\Exceptions\PriceAlreadyActive;
use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle activates an existing inactive price', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    app(DeactivatePriceHandler::class)->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));
    $handler = app(ActivatePriceHandler::class);

    $activated = $handler->handle(new ActivatePriceCommand($merchantId->toString(), $price->id()->toString()));

    expect($activated->status())->toBe(PriceStatus::Active);
    $persisted = app(IPriceRepositoryPort::class)->get($price->id(), $merchantId);
    expect($persisted->status())->toBe(PriceStatus::Active);
});

test('handle throws PriceNotFound when the price does not exist', function () {
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand(MerchantId::generate()->toString(), PriceId::generate()->toString()));
})->throws(PriceNotFound::class);

test('handle throws PriceNotFound when the price belongs to a different merchant', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    app(DeactivatePriceHandler::class)->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand(MerchantId::generate()->toString(), $price->id()->toString()));
})->throws(PriceNotFound::class);

test('handle throws PriceAlreadyActive when the price is already active', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand($merchantId->toString(), $price->id()->toString()));
})->throws(PriceAlreadyActive::class);

test('handle records a PriceActivated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    app(DeactivatePriceHandler::class)->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));
    $handler = app(ActivatePriceHandler::class);

    $handler->handle(new ActivatePriceCommand($merchantId->toString(), $price->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $activated = collect($unpublished)->firstWhere('eventType', 'price.activated.v1');
    expect($activated)->not->toBeNull()
        ->and($activated->aggregateId)->toBe($price->id()->toString());
});
