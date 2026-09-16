<?php

use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request deactivates an existing active price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');

    $response = $this->postJson("/api/prices/{$price['id']}/deactivate");

    $response->assertOk()->assertJsonPath('data.status', 'inactive');
});

test('a request for a non-existent price returns not found', function () {
    $response = $this->postJson('/api/prices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/deactivate');

    $response->assertNotFound();
});

test('a request to deactivate an already-inactive price returns a conflict', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');
    $this->postJson("/api/prices/{$price['id']}/deactivate");

    $response = $this->postJson("/api/prices/{$price['id']}/deactivate");

    $response->assertConflict();
});
