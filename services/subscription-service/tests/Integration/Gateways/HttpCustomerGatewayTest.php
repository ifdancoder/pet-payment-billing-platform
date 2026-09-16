<?php

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Infrastructure\Subscription\Adapters\Gateways\HttpCustomerGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

test('find returns customer data when the customer exists', function () {
    $id = CustomerId::generate();
    Http::fake([
        "https://customers.internal/api/v1/customers/{$id->toString()}" => Http::response([
            'id' => $id->toString(),
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ], 200),
    ]);

    $gateway = new HttpCustomerGateway('https://customers.internal');
    $customer = $gateway->find($id);

    expect($customer)->not->toBeNull()
        ->and($customer->id)->toBe($id->toString())
        ->and($customer->email)->toBe('jane@example.com')
        ->and($customer->name)->toBe('Jane Doe');
});

test('find returns null when the customer does not exist', function () {
    $id = CustomerId::generate();
    Http::fake([
        "https://customers.internal/api/v1/customers/{$id->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    $gateway = new HttpCustomerGateway('https://customers.internal');

    expect($gateway->find($id))->toBeNull();
});

test('find throws when customer-service responds with a server error', function () {
    $id = CustomerId::generate();
    Http::fake([
        "https://customers.internal/api/v1/customers/{$id->toString()}" => Http::response(['message' => 'boom'], 500),
    ]);

    $gateway = new HttpCustomerGateway('https://customers.internal');

    $gateway->find($id);
})->throws(RequestException::class);
