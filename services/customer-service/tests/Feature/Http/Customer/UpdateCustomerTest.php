<?php

use App\Domain\Customer\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Mail;

test('a valid request updates the customer and returns it', function () {
    Mail::fake();
    $created = $this->postJson('/api/v1/customers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $response = $this->putJson("/api/v1/customers/{$created['id']}", [
        'email' => 'jane.doe@example.com',
        'name' => 'Jane Smith',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'jane.doe@example.com')
        ->assertJsonPath('data.name', 'Jane Smith');
});

test('a request missing required fields is rejected', function () {
    Mail::fake();
    $created = $this->postJson('/api/v1/customers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $response = $this->putJson("/api/v1/customers/{$created['id']}", []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'name']);
});

test('a request for a missing customer returns 404', function () {
    $response = $this->putJson('/api/v1/customers/'.CustomerId::generate()->toString(), [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $response->assertNotFound();
});
