<?php

use App\Domain\Price\ValueObjects\PriceType;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request returns the product together with its prices', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ]);

    $response = $this->getJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product['id'])
        ->assertJsonCount(1, 'data.prices');
});

test('a request for a product with no prices returns an empty prices array', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->getJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}");

    $response->assertOk()->assertJsonCount(0, 'data.prices');
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for a product belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/products/{$product['id']}");

    $response->assertNotFound();
});
