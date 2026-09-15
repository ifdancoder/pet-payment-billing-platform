<?php

test('a request archives an existing product', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');

    $response = $this->postJson("/api/products/{$product['id']}/archive");

    $response->assertOk()->assertJsonPath('data.status', 'archived');
});

test('a request for a non-existent product returns not found', function () {
    $response = $this->postJson('/api/products/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/archive');

    $response->assertNotFound();
});

test('a request to archive an already-archived product returns a conflict', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');
    $this->postJson("/api/products/{$product['id']}/archive");

    $response = $this->postJson("/api/products/{$product['id']}/archive");

    $response->assertConflict();
});
