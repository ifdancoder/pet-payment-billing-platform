<?php

use App\Shared\Domain\ValueObjects\MerchantId;

test('a valid request renames an existing product', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->patchJson("/api/merchants/{$merchantId}/products/{$product['id']}", ['name' => 'Pro Plan v2']);

    $response->assertOk()->assertJsonPath('data.name', 'Pro Plan v2');
});

test('a request missing required fields is rejected', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->patchJson("/api/merchants/{$merchantId}/products/{$product['id']}", []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->patchJson('/api/merchants/'.MerchantId::generate()->toString().'/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab', ['name' => 'Pro Plan v2']);

    $response->assertNotFound();
});

test('a request for a product belonging to a different merchant returns not found', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');

    $response = $this->patchJson('/api/merchants/'.MerchantId::generate()->toString()."/products/{$product['id']}", ['name' => 'Pro Plan v2']);

    $response->assertNotFound();
});

test('a request to update an archived product returns a conflict', function () {
    $merchantId = MerchantId::generate()->toString();
    $product = $this->postJson("/api/merchants/{$merchantId}/products", ['name' => 'Pro Plan'])->json('data');
    $this->postJson("/api/merchants/{$merchantId}/products/{$product['id']}/archive");

    $response = $this->patchJson("/api/merchants/{$merchantId}/products/{$product['id']}", ['name' => 'Pro Plan v2']);

    $response->assertConflict();
});
