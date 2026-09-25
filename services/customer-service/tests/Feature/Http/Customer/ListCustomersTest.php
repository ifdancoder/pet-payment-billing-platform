<?php

use Illuminate\Support\Facades\Mail;

test('a request returns every existing customer', function () {
    Mail::fake();
    $this->postJson(customerApi(), ['email' => 'jane@example.com', 'name' => 'Jane Doe']);
    $this->postJson(customerApi(), ['email' => 'john@example.com', 'name' => 'John Doe']);

    $response = $this->getJson(customerApi());

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no customers', function () {
    $response = $this->getJson(customerApi());

    $response->assertOk()->assertJsonCount(0, 'data');
});
