<?php

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Catalog\Adapters\Persistence\Queries\EloquentCatalogQuery;
use App\Infrastructure\Price\Adapters\Persistence\Mappers\PriceMapper;
use App\Infrastructure\Price\Adapters\Persistence\Repositories\EloquentPriceRepository;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('getProductCatalog returns the product with every one of its prices', function () {
    $productId = ProductId::generate();
    $merchantId = MerchantId::generate();
    (new EloquentProductRepository(new ProductMapper))
        ->save(Product::create($productId, $merchantId, ProductName::fromString('Pro Plan')));
    $priceRepository = new EloquentPriceRepository(new PriceMapper);
    $priceRepository->save(Price::create(
        PriceId::generate(),
        $merchantId,
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    ));
    $priceRepository->save(Price::create(
        PriceId::generate(),
        $merchantId,
        $productId,
        Money::of(19990, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Year, 1),
    ));

    $catalog = (new EloquentCatalogQuery(new ProductMapper, new PriceMapper))->getProductCatalog($productId, $merchantId);

    expect($catalog->product->id()->equals($productId))->toBeTrue()
        ->and($catalog->prices)->toHaveCount(2)
        ->and(array_map(fn (Price $p) => $p->money()->amountMinorUnits(), $catalog->prices))
        ->toEqualCanonicalizing([1999, 19990]);
});

test('getProductCatalog returns an empty prices array when the product has none', function () {
    $productId = ProductId::generate();
    $merchantId = MerchantId::generate();
    (new EloquentProductRepository(new ProductMapper))
        ->save(Product::create($productId, $merchantId, ProductName::fromString('Pro Plan')));

    $catalog = (new EloquentCatalogQuery(new ProductMapper, new PriceMapper))->getProductCatalog($productId, $merchantId);

    expect($catalog->prices)->toBe([]);
});

test('getProductCatalog throws ProductNotFound when the product does not exist', function () {
    (new EloquentCatalogQuery(new ProductMapper, new PriceMapper))->getProductCatalog(ProductId::generate(), MerchantId::generate());
})->throws(ProductNotFound::class);

test('getProductCatalog throws ProductNotFound when the product belongs to a different merchant', function () {
    $productId = ProductId::generate();
    (new EloquentProductRepository(new ProductMapper))
        ->save(Product::create($productId, MerchantId::generate(), ProductName::fromString('Pro Plan')));

    (new EloquentCatalogQuery(new ProductMapper, new PriceMapper))->getProductCatalog($productId, MerchantId::generate());
})->throws(ProductNotFound::class);
