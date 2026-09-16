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
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle deactivates an existing active price', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(DeactivatePriceHandler::class);

    $deactivated = $handler->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));

    expect($deactivated->status())->toBe(PriceStatus::Inactive);
    $persisted = app(IPriceRepositoryPort::class)->get($price->id(), $merchantId);
    expect($persisted->status())->toBe(PriceStatus::Inactive);
});

test('handle throws PriceNotFound when the price does not exist', function () {
    $handler = app(DeactivatePriceHandler::class);

    $handler->handle(new DeactivatePriceCommand(MerchantId::generate()->toString(), PriceId::generate()->toString()));
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
    $handler = app(DeactivatePriceHandler::class);

    $handler->handle(new DeactivatePriceCommand(MerchantId::generate()->toString(), $price->id()->toString()));
})->throws(PriceNotFound::class);

test('handle throws PriceAlreadyInactive when the price is already inactive', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(DeactivatePriceHandler::class);
    $handler->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));

    $handler->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));
})->throws(PriceAlreadyInactive::class);

test('handle records a PriceDeactivated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $price = app(CreatePriceHandler::class)->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
    $handler = app(DeactivatePriceHandler::class);

    $handler->handle(new DeactivatePriceCommand($merchantId->toString(), $price->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $deactivated = collect($unpublished)->firstWhere('eventType', 'price.deactivated.v1');
    expect($deactivated)->not->toBeNull()
        ->and($deactivated->aggregateId)->toBe($price->id()->toString());
});
