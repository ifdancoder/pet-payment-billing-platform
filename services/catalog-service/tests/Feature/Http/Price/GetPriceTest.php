<?php

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request returns the matching price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::Recurring->value,
        'billing_interval' => BillingInterval::Month->value,
        'billing_interval_count' => 1,
    ])->json('data');

    $response = $this->getJson("/api/v1/merchants/{$merchantId}/prices/{$price['id']}");

    $response->assertOk()->assertJsonPath('data.id', $price['id']);
});

test('a request for a non-existent price returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/prices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for a price belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/prices/{$price['id']}");

    $response->assertNotFound();
});
