<?php

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\ValueObjects\CustomerId;
use Illuminate\Support\Facades\Mail;

test('a valid request creates a customer and returns it', function () {
    Mail::fake();

    $response = $this->postJson(customerApi(), [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'merchant_id', 'email', 'name']])
        ->assertJsonPath('data.merchant_id', aMerchantId()->toString())
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.name', 'Jane Doe');

    $customer = app(ICustomerRepositoryPort::class)->get(
        CustomerId::fromString($response->json('data.id')),
        aMerchantId(),
    );
    expect($customer->email()->toString())->toBe('jane@example.com');
});

test('a request missing required fields is rejected', function () {
    $response = $this->postJson(customerApi(), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'name']);
});

test('a request with an invalid email is rejected', function () {
    $response = $this->postJson(customerApi(), [
        'email' => 'not-an-email',
        'name' => 'Jane Doe',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});
