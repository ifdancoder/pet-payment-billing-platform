<?php

use Illuminate\Support\Facades\Mail;

test('a request returns every existing customer', function () {
    Mail::fake();
    $this->postJson('/api/v1/customers', ['email' => 'jane@example.com', 'name' => 'Jane Doe']);
    $this->postJson('/api/v1/customers', ['email' => 'john@example.com', 'name' => 'John Doe']);

    $response = $this->getJson('/api/v1/customers');

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no customers', function () {
    $response = $this->getJson('/api/v1/customers');

    $response->assertOk()->assertJsonCount(0, 'data');
});
