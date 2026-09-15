<?php

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;

test('toDomain builds a Price matching the model attributes', function () {
    $model = new PriceModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'product_id' => '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
    ]);

    $price = (new PriceMapper)->toDomain($model);

    expect($price->id()->toString())->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($price->productId()->toString())->toBe('1a2b3c4d-5e6f-4321-8765-0123456789ab')
        ->and($price->money()->amountMinorUnits())->toBe(1999)
        ->and($price->money()->currency())->toBe(Currency::USD)
        ->and($price->billingInterval())->toBe(BillingInterval::Monthly);
});

test('toDomain does not record a PriceCreated event', function () {
    $model = new PriceModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'product_id' => '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
    ]);

    $price = (new PriceMapper)->toDomain($model);

    expect($price->pullRecordedEvents())->toBe([]);
});

test('toModel fills a new model from a Price', function () {
    $price = Price::create(
        PriceId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        ProductId::fromString('1a2b3c4d-5e6f-4321-8765-0123456789ab'),
        Money::of(1999, Currency::USD),
        BillingInterval::Monthly,
    );

    $model = (new PriceMapper)->toModel($price);

    expect($model->id)->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($model->product_id)->toBe('1a2b3c4d-5e6f-4321-8765-0123456789ab')
        ->and($model->amount_minor_units)->toBe(1999)
        ->and($model->currency)->toBe('USD')
        ->and($model->billing_interval)->toBe('monthly');
});

test('toModel fills an existing model instance in place instead of creating a new one', function () {
    $existing = new PriceModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'product_id' => '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        'amount_minor_units' => 999,
        'currency' => 'USD',
        'billing_interval' => 'yearly',
    ]);
    $price = Price::create(
        PriceId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        ProductId::fromString('1a2b3c4d-5e6f-4321-8765-0123456789ab'),
        Money::of(1999, Currency::EUR),
        BillingInterval::Monthly,
    );

    $model = (new PriceMapper)->toModel($price, $existing);

    expect($model)->toBe($existing)
        ->and($model->amount_minor_units)->toBe(1999)
        ->and($model->currency)->toBe('EUR')
        ->and($model->billing_interval)->toBe('monthly');
});
