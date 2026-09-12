<?php

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Mail;

test('a valid request creates a customer and returns its id', function () {
    Mail::fake();

    $response = $this->postJson('/api/customers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $response->assertCreated()->assertJsonStructure(['id']);

    $customer = app(ICustomerRepositoryPort::class)->findById(
        CustomerId::fromString($response->json('id')),
    );
    expect($customer)->not->toBeNull()
        ->and($customer->email()->toString())->toBe('jane@example.com');
});

test('a request missing required fields is rejected', function () {
    $response = $this->postJson('/api/customers', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'name']);
});

test('a request with an invalid email is rejected', function () {
    $response = $this->postJson('/api/customers', [
        'email' => 'not-an-email',
        'name' => 'Jane Doe',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});
