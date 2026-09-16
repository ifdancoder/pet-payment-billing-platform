<?php

use App\Domain\Customer\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Mail;

test('a request for an existing customer returns it', function () {
    Mail::fake();
    $created = $this->postJson('/api/v1/customers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $response = $this->getJson("/api/v1/customers/{$created['id']}");

    $response->assertOk()
        ->assertJsonPath('data.id', $created['id'])
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.name', 'Jane Doe');
});

test('a request for a missing customer returns 404', function () {
    $response = $this->getJson('/api/v1/customers/'.CustomerId::generate()->toString());

    $response->assertNotFound();
});
