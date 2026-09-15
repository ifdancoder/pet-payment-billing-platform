<?php

use App\Domain\Price\ValueObjects\PriceType;

test('a request activates an existing inactive price', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');
    $this->postJson("/api/prices/{$price['id']}/deactivate");

    $response = $this->postJson("/api/prices/{$price['id']}/activate");

    $response->assertOk()->assertJsonPath('data.status', 'active');
});

test('a request for a non-existent price returns not found', function () {
    $response = $this->postJson('/api/prices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/activate');

    $response->assertNotFound();
});

test('a request to activate an already-active price returns a conflict', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::OneTime->value,
    ])->json('data');

    $response = $this->postJson("/api/prices/{$price['id']}/activate");

    $response->assertConflict();
});
