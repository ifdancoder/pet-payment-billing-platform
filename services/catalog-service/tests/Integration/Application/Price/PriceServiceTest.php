<?php

use App\Application\Price\Commands\ActivatePrice\ActivatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceCommand;
use App\Application\Price\PriceService;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\ProductService;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('createPrice delegates to CreatePriceHandler and returns the created price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = app(ProductService::class)->createProduct(new CreateProductCommand($merchantId, 'Pro Plan'));
    $service = app(PriceService::class);

    $price = $service->createPrice(new CreatePriceCommand(
        $merchantId,
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));

    expect($price->money()->amountMinorUnits())->toBe(1999);
});

test('getPrice delegates to GetPriceHandler and returns the matching price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = app(ProductService::class)->createProduct(new CreateProductCommand($merchantId, 'Pro Plan'));
    $service = app(PriceService::class);
    $created = $service->createPrice(new CreatePriceCommand(
        $merchantId,
        $product->id()->toString(),
        1999,
        'USD',
        PriceType::Recurring->value,
        BillingInterval::Month->value,
        1,
    ));

    $found = $service->getPrice(new GetPriceQuery($merchantId, $created->id()->toString()));

    expect($found->id()->equals($created->id()))->toBeTrue();
});

test('deactivatePrice delegates to DeactivatePriceHandler and returns the deactivated price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = app(ProductService::class)->createProduct(new CreateProductCommand($merchantId, 'Pro Plan'));
    $service = app(PriceService::class);
    $price = $service->createPrice(new CreatePriceCommand($merchantId, $product->id()->toString(), 1999, 'USD', PriceType::OneTime->value));

    $deactivated = $service->deactivatePrice(new DeactivatePriceCommand($merchantId, $price->id()->toString()));

    expect($deactivated->status())->toBe(PriceStatus::Inactive);
});

test('activatePrice delegates to ActivatePriceHandler and returns the activated price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = app(ProductService::class)->createProduct(new CreateProductCommand($merchantId, 'Pro Plan'));
    $service = app(PriceService::class);
    $price = $service->createPrice(new CreatePriceCommand($merchantId, $product->id()->toString(), 1999, 'USD', PriceType::OneTime->value));
    $service->deactivatePrice(new DeactivatePriceCommand($merchantId, $price->id()->toString()));

    $activated = $service->activatePrice(new ActivatePriceCommand($merchantId, $price->id()->toString()));

    expect($activated->status())->toBe(PriceStatus::Active);
});
