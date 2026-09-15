<?php

test('a request returns every existing product', function () {
    $this->postJson('/api/products', ['name' => 'Pro Plan']);
    $this->postJson('/api/products', ['name' => 'Team Plan']);

    $response = $this->getJson('/api/products');

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no products', function () {
    $response = $this->getJson('/api/products');

    $response->assertOk()->assertJsonCount(0, 'data');
});
