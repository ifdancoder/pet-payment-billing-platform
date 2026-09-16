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
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle persists a new recurring price for an existing product', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand(
        $merchantId->toString(),
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
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand(
        $merchantId->toString(),
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
        MerchantId::generate()->toString(),
        ProductId::generate()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));
})->throws(ProductNotFound::class);

test('handle throws ProductNotFound when the product belongs to a different merchant', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand(MerchantId::generate()->toString(), 'Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $handler->handle(new CreatePriceCommand(
        MerchantId::generate()->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::OneTime->value,
    ));
})->throws(ProductNotFound::class);

test('handle throws InvalidPrice when a recurring price is requested without a billing period', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $handler->handle(new CreatePriceCommand(
        $merchantId->toString(),
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
    ));
})->throws(InvalidPrice::class);

test('handle records a PriceCreated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(CreatePriceHandler::class);

    $price = $handler->handle(new CreatePriceCommand($merchantId->toString(), $product->id()->toString(), 1999, 'USD', PriceType::OneTime->value));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $created = collect($unpublished)->firstWhere('eventType', 'price.created.v1');
    expect($created)->not->toBeNull()
        ->and($created->aggregateId)->toBe($price->id()->toString())
        ->and($created->payload['amount_minor_units'])->toBe(1999);
});
