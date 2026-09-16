<?php

use App\Shared\Domain\ValueObjects\MerchantId;

test('a request returns every existing product for the given merchant', function () {
    $merchantId = MerchantId::generate()->toString();
    $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan']);
    $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Team Plan']);
    $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/products', ['name' => 'Other Merchant Plan']);

    $response = $this->getJson("/api/v1/merchants/{$merchantId}/products");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no products for the given merchant', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/products');

    $response->assertOk()->assertJsonCount(0, 'data');
});
