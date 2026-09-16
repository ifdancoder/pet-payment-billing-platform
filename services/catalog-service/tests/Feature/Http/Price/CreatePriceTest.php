<?php

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a valid request creates a recurring price for the product and returns it', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::Recurring->value,
        'billing_interval' => BillingInterval::Month->value,
        'billing_interval_count' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => [
            'id', 'product_id', 'amount_minor_units', 'currency', 'type', 'billing_interval', 'billing_interval_count', 'status',
        ]])
        ->assertJsonPath('data.product_id', $product['id'])
        ->assertJsonPath('data.amount_minor_units', 1999)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.type', 'recurring')
        ->assertJsonPath('data.billing_interval', 'month')
        ->assertJsonPath('data.billing_interval_count', 1);
});

test('a valid request creates a one-time price for the product and returns it', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 4999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'one_time')
        ->assertJsonPath('data.billing_interval', null)
        ->assertJsonPath('data.billing_interval_count', null);
});

test('a request missing required fields is rejected', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount_minor_units', 'currency', 'type']);
});

test('a recurring price request without a billing interval is rejected', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::Recurring->value,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['billing_interval', 'billing_interval_count']);
});

test('a one-time price request with a billing interval is rejected', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 4999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
        'billing_interval' => BillingInterval::Month->value,
        'billing_interval_count' => 1,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['billing_interval', 'billing_interval_count']);
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->postJson('/api/merchants/'.MerchantId::generate()->toString().'/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/prices', [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::Recurring->value,
        'billing_interval' => BillingInterval::Month->value,
        'billing_interval_count' => 1,
    ]);

    $response->assertNotFound();
});

test('a request for a product belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson('/api/merchants/'.MerchantId::generate()->toString()."/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ]);

    $response->assertNotFound();
});
