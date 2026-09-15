<?php

test('a valid request renames an existing product', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');

    $response = $this->patchJson("/api/products/{$product['id']}", ['name' => 'Pro Plan v2']);

    $response->assertOk()->assertJsonPath('data.name', 'Pro Plan v2');
});

test('a request missing required fields is rejected', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');

    $response = $this->patchJson("/api/products/{$product['id']}", []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->patchJson('/api/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab', ['name' => 'Pro Plan v2']);

    $response->assertNotFound();
});

test('a request to update an archived product returns a conflict', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');
    $this->postJson("/api/products/{$product['id']}/archive");

    $response = $this->patchJson("/api/products/{$product['id']}", ['name' => 'Pro Plan v2']);

    $response->assertConflict();
});
