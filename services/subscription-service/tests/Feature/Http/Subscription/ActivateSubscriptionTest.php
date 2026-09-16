<?php

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

test('a valid request activates a Pending subscription', function () {
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

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions/{$subscription['id']}/activate");

    $response->assertOk()->assertJsonPath('data.status', 'active');
});

test('a request for a non-existent subscription returns not found', function () {
    $response = $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/subscriptions/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/activate');

    $response->assertNotFound();
});

test('a request to activate an already-active subscription returns a conflict', function () {
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
    $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions/{$subscription['id']}/activate");

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions/{$subscription['id']}/activate");

    $response->assertConflict();
});
