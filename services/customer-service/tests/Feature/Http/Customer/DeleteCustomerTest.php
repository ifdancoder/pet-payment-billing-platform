<?php

use App\Domain\Customer\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Mail;

test('a valid request deletes the customer and returns no content', function () {
    Mail::fake();
    $created = $this->postJson('/api/customers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $response = $this->deleteJson("/api/customers/{$created['id']}");

    $response->assertNoContent();
    $this->getJson("/api/customers/{$created['id']}")->assertNotFound();
});

test('a request for a missing customer returns 404', function () {
    $response = $this->deleteJson('/api/customers/'.CustomerId::generate()->toString());

    $response->assertNotFound();
});
