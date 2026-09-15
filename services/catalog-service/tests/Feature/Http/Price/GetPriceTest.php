<?php

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\PriceType;

test('a request returns the matching price', function () {
    $product = $this->postJson('/api/products', ['name' => 'Pro Plan'])->json('data');
    $price = $this->postJson("/api/products/{$product['id']}/prices", [
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => PriceType::Recurring->value,
        'billing_interval' => BillingInterval::Month->value,
        'billing_interval_count' => 1,
    ])->json('data');

    $response = $this->getJson("/api/prices/{$price['id']}");

    $response->assertOk()->assertJsonPath('data.id', $price['id']);
});

test('a request for a non-existent price returns not found', function () {
    $response = $this->getJson('/api/prices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});
