<?php

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

function fakeSubscriptionUpstream(MerchantId $merchantId, CustomerId $customerId, PriceId $priceId): void
{
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
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
}

test('a request returns every existing subscription for the given merchant', function () {
    $merchantId = MerchantId::generate();

    $firstCustomerId = CustomerId::generate();
    $firstPriceId = PriceId::generate();
    fakeSubscriptionUpstream($merchantId, $firstCustomerId, $firstPriceId);
    $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $firstCustomerId->toString(),
        'price_id' => $firstPriceId->toString(),
    ])->assertCreated();

    $secondCustomerId = CustomerId::generate();
    $secondPriceId = PriceId::generate();
    fakeSubscriptionUpstream($merchantId, $secondCustomerId, $secondPriceId);
    $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $secondCustomerId->toString(),
        'price_id' => $secondPriceId->toString(),
    ])->assertCreated();

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no subscriptions for the given merchant', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/subscriptions');

    $response->assertOk()->assertJsonCount(0, 'data');
});
