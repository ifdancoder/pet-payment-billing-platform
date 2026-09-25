<?php

use App\Domain\Customer\ValueObjects\CustomerId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Mail;

test('a request for an existing customer returns it', function () {
    Mail::fake();
    $created = $this->postJson(customerApi(), [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $response = $this->getJson(customerApi("/{$created['id']}"));

    $response->assertOk()
        ->assertJsonPath('data.id', $created['id'])
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.name', 'Jane Doe');
});

test('a request for a missing customer returns 404', function () {
    $response = $this->getJson(customerApi('/'.CustomerId::generate()->toString()));

    $response->assertNotFound();
});

test('a customer cannot be read through another merchant tenant', function () {
    Mail::fake();
    $created = $this->postJson(customerApi(), [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ])->json('data');

    $otherMerchant = MerchantId::fromString('22222222-2222-4222-8222-222222222222');
    $this->getJson("/api/v1/merchants/{$otherMerchant->toString()}/customers/{$created['id']}")
        ->assertNotFound();
});
