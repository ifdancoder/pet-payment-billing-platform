<?php

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

test('a request returns the matching subscription', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane'], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'id' => $priceId->toString(),
            'product_id' => '11111111-1111-4111-8111-111111111111',
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'type' => 'recurring',
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
            'status' => 'active',
        ], 200),
    ]);
    $subscription = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ])->json('data');

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions/{$subscription['id']}");

    $response->assertOk()->assertJsonPath('data.id', $subscription['id']);
});

test('a request for a non-existent subscription returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/subscriptions/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for a subscription belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane'], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'id' => $priceId->toString(),
            'product_id' => '11111111-1111-4111-8111-111111111111',
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'type' => 'recurring',
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
            'status' => 'active',
        ], 200),
    ]);
    $subscription = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ])->json('data');

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/subscriptions/{$subscription['id']}");

    $response->assertNotFound();
});
