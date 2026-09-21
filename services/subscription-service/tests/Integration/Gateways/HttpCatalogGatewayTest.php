<?php

use App\Domain\Subscription\ValueObjects\PriceId;
use App\Infrastructure\Subscription\Adapters\Gateways\HttpCatalogGateway;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

test('findPrice returns price data when the price exists', function () {
    $merchantId = MerchantId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "https://catalog.internal/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
                'merchant_id' => $merchantId->toString(),
                'product_id' => '11111111-1111-4111-8111-111111111111',
                'amount_minor_units' => 1999,
                'currency' => 'USD',
                'type' => 'recurring',
                'billing_interval' => 'month',
                'billing_interval_count' => 1,
                'status' => 'active',
            ],
        ], 200),
    ]);

    $gateway = new HttpCatalogGateway('https://catalog.internal');
    $price = $gateway->findPrice($merchantId, $priceId);

    expect($price)->not->toBeNull()
        ->and($price->priceId)->toBe($priceId->toString())
        ->and($price->productId)->toBe('11111111-1111-4111-8111-111111111111')
        ->and($price->amountMinorUnits)->toBe(1999)
        ->and($price->currency)->toBe('USD')
        ->and($price->type)->toBe('recurring')
        ->and($price->billingInterval)->toBe('month')
        ->and($price->billingIntervalCount)->toBe(1)
        ->and($price->status)->toBe('active')
        ->and($price->isRecurring())->toBeTrue()
        ->and($price->isActive())->toBeTrue();
});

test('findPrice returns null when the price does not exist for the given merchant', function () {
    $merchantId = MerchantId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "https://catalog.internal/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    $gateway = new HttpCatalogGateway('https://catalog.internal');

    expect($gateway->findPrice($merchantId, $priceId))->toBeNull();
});

test('findPrice throws when catalog-service responds with a server error', function () {
    $merchantId = MerchantId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "https://catalog.internal/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response(['message' => 'boom'], 500),
    ]);

    $gateway = new HttpCatalogGateway('https://catalog.internal');

    $gateway->findPrice($merchantId, $priceId);
})->throws(RequestException::class);
