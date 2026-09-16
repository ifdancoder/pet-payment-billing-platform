<?php

use App\Application\Catalog\ReadModels\ProductCatalog;
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
use App\Presentation\Product\Adapters\Inbound\Http\Resources\ProductCatalogResource;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Http\Request;

test('toArray exposes the product fields together with its prices', function () {
    $productId = ProductId::generate();
    $merchantId = MerchantId::generate();
    $product = Product::create($productId, $merchantId, ProductName::fromString('Pro Plan'), 'Pro tier subscription');
    $priceId = PriceId::generate();
    $price = Price::create(
        $priceId,
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    );
    $catalog = new ProductCatalog($product, [$price]);

    $array = (new ProductCatalogResource($catalog))->toArray(new Request);

    expect($array)->toBe([
        'id' => $productId->toString(),
        'merchant_id' => $merchantId->toString(),
        'name' => 'Pro Plan',
        'description' => 'Pro tier subscription',
        'status' => 'active',
        'prices' => [
            [
                'id' => $priceId->toString(),
                'product_id' => $productId->toString(),
                'amount_minor_units' => 1999,
                'currency' => 'USD',
                'type' => 'recurring',
                'billing_interval' => 'month',
                'billing_interval_count' => 1,
                'status' => 'active',
            ],
        ],
    ]);
});

test('toArray exposes an empty prices array when the product has none', function () {
    $product = Product::create(ProductId::generate(), MerchantId::generate(), ProductName::fromString('Pro Plan'));
    $catalog = new ProductCatalog($product, []);

    $array = (new ProductCatalogResource($catalog))->toArray(new Request);

    expect($array['prices'])->toBe([]);
});

test('constructing with a non-ProductCatalog value fails with a TypeError', function () {
    new ProductCatalogResource('not-a-catalog');
})->throws(TypeError::class);
