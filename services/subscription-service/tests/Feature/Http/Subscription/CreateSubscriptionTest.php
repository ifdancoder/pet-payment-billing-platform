<?php

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

test('a valid request creates a subscription and returns it', function () {
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

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => [
            'id', 'merchant_id', 'customer_id', 'price_id', 'product_id',
            'amount_minor_units', 'currency', 'billing_interval', 'billing_interval_count', 'status',
        ]])
        ->assertJsonPath('data.merchant_id', $merchantId->toString())
        ->assertJsonPath('data.customer_id', $customerId->toString())
        ->assertJsonPath('data.price_id', $priceId->toString())
        ->assertJsonPath('data.amount_minor_units', 1999)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.billing_interval', 'month')
        ->assertJsonPath('data.billing_interval_count', 1)
        ->assertJsonPath('data.status', 'pending');
});

test('a request missing required fields is rejected', function () {
    $merchantId = MerchantId::generate();

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'price_id']);
});

test('a request for a non-existent customer returns not found', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ]);

    $response->assertNotFound();
});

test('a request for a non-existent price returns not found', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane'], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ]);

    $response->assertNotFound();
});

test('a request for a one-time price returns a conflict', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane'], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'id' => $priceId->toString(),
            'product_id' => '11111111-1111-4111-8111-111111111111',
            'amount_minor_units' => 4999,
            'currency' => 'USD',
            'type' => 'one_time',
            'billing_interval' => null,
            'billing_interval_count' => null,
            'status' => 'active',
        ], 200),
    ]);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ]);

    $response->assertConflict();
});

test('a request for an inactive price returns a conflict', function () {
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
            'status' => 'inactive',
        ], 200),
    ]);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/subscriptions", [
        'customer_id' => $customerId->toString(),
        'price_id' => $priceId->toString(),
    ]);

    $response->assertConflict();
});
