<?php

test('a valid request creates a price for the product and returns it', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'product_id', 'amount_minor_units', 'currency', 'billing_interval']])
        ->assertJsonPath('data.product_id', $product['id'])
        ->assertJsonPath('data.amount_minor_units', 1999)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.billing_interval', 'monthly');
});

test('a request missing required fields is rejected', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/products/{$product['id']}/prices", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount_minor_units', 'currency', 'billing_interval']);
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->postJson('/api/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/prices', [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
    ]);

    $response->assertNotFound();
});
