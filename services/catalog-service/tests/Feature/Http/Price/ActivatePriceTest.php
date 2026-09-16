<?php

use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request activates an existing inactive price', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');
    $this->postJson("/api/merchants/{$merchantId}/prices/{$price['id']}/deactivate");

    $response = $this->postJson("/api/merchants/{$merchantId}/prices/{$price['id']}/activate");

    $response->assertOk()->assertJsonPath('data.status', 'active');
});

test('a request for a non-existent price returns not found', function () {
    $response = $this->postJson('/api/merchants/'.MerchantId::generate()->toString().'/prices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/activate');

    $response->assertNotFound();
});

test('a request for a price belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');
    $this->postJson("/api/merchants/{$merchantId}/prices/{$price['id']}/deactivate");

    $response = $this->postJson('/api/merchants/'.MerchantId::generate()->toString()."/prices/{$price['id']}/activate");

    $response->assertNotFound();
});

test('a request to activate an already-active price returns a conflict', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');

    $response = $this->postJson("/api/merchants/{$merchantId}/prices/{$price['id']}/activate");

    $response->assertConflict();
});
