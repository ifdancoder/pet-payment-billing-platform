<?php

use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;
use App\Infrastructure\Price\Adapters\Persistence\Repositories\EloquentPriceRepository;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;

function persistProduct(ProductId $productId): void
{
    (new EloquentProductRepository(new ProductMapper))
        ->save(Product::create($productId, ProductName::fromString('Pro Plan')));
}

test('save persists a new recurring price', function () {
    $repository = new EloquentPriceRepository(new PriceMapper);
    $productId = ProductId::generate();
    persistProduct($productId);
    $price = Price::create(
        PriceId::generate(),
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    );

    $repository->save($price);

    expect(PriceModel::query()->where('id', $price->id()->toString())->exists())->toBeTrue();
});

test('save persists a new one-time price', function () {
    $repository = new EloquentPriceRepository(new PriceMapper);
    $productId = ProductId::generate();
    persistProduct($productId);
    $price = Price::create(PriceId::generate(), $productId, Money::of(4999, Currency::USD), PriceType::OneTime);

    $repository->save($price);

    $model = PriceModel::query()->find($price->id()->toString());
    expect($model)->not->toBeNull()
        ->and($model->billing_interval)->toBeNull();
});

test('save updates an already-persisted price instead of duplicating it', function () {
    $repository = new EloquentPriceRepository(new PriceMapper);
    $productId = ProductId::generate();
    persistProduct($productId);
    $id = PriceId::generate();
    $repository->save(Price::create(
        $id,
        $productId,
        Money::of(999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    ));

    $repository->save(Price::reconstitute(
        $id,
        $productId,
        Money::of(1999, Currency::EUR),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Year, 1),
    ));

    expect(PriceModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(PriceModel::query()->find($id->toString())->amount_minor_units)->toBe(1999);
});

test('get returns the matching price', function () {
    $repository = new EloquentPriceRepository(new PriceMapper);
    $productId = ProductId::generate();
    persistProduct($productId);
    $id = PriceId::generate();
    $repository->save(Price::create(
        $id,
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    ));

    $found = $repository->get($id);

    expect($found->id()->equals($id))->toBeTrue()
        ->and($found->productId()->equals($productId))->toBeTrue()
        ->and($found->money()->amountMinorUnits())->toBe(1999)
        ->and($found->billingPeriod()->interval())->toBe(BillingInterval::Month);
});

test('get throws PriceNotFound when no price matches', function () {
    $repository = new EloquentPriceRepository(new PriceMapper);

    $repository->get(PriceId::generate());
})->throws(PriceNotFound::class);
