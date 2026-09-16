<?php

use App\Shared\Domain\ValueObjects\MerchantId;

test('a request archives an existing product', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/archive");

    $response->assertOk()->assertJsonPath('data.status', 'archived');
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/archive');

    $response->assertNotFound();
});

test('a request for a product belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/products/{$product['id']}/archive");

    $response->assertNotFound();
});

test('a request to archive an already-archived product returns a conflict', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/v1/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/archive");

    $response = $this->postJson("/api/v1/merchants/{$merchantId}/products/{$product['id']}/archive");

    $response->assertConflict();
});
