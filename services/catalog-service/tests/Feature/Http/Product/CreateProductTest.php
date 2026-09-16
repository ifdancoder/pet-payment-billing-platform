<?php

use App\Shared\Domain\ValueObjects\MerchantId;

test('a valid request creates a product and returns it', function () {
    $merchantId = MerchantId::generate()->toString();

    $response = $this->postJson("/api/v1/merchants/{$merchantId}/products", [
        'name' => 'Pro Plan',
        'description' => 'Pro tier subscription',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'merchant_id', 'name', 'description', 'status']])
        ->assertJsonPath('data.merchant_id', $merchantId)
        ->assertJsonPath('data.name', 'Pro Plan')
        ->assertJsonPath('data.description', 'Pro tier subscription');
});

test('a valid request without a description creates a product with a null description', function () {
    $merchantId = MerchantId::generate()->toString();

    $response = $this->postJson("/api/v1/merchants/{$merchantId}/products", [
        'name' => 'Pro Plan',
    ]);

    $response->assertCreated()->assertJsonPath('data.description', null);
});

test('a request missing required fields is rejected', function () {
    $response = $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/products', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
