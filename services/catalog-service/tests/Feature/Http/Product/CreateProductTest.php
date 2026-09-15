<?php

test('a valid request creates a product and returns it', function () {
    $response = $this->postJson('/api/products', [
        'name' => 'Pro Plan',
    ]);

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'name']])
        ->assertJsonPath('data.name', 'Pro Plan');
});

test('a request missing required fields is rejected', function () {
    $response = $this->postJson('/api/products', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
