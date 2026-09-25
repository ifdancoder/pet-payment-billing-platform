<?php

use App\Infrastructure\Notification\Adapters\Gateways\HttpCustomerContactGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

test('find returns customer contact details when the customer exists', function () {
    $merchantId = (string) Str::uuid();
    $id = (string) Str::uuid();
    Http::fake([
        "https://customers.internal/api/v1/merchants/{$merchantId}/customers/{$id}" => Http::response([
            'data' => [
                'id' => $id,
                'email' => 'jane@example.com',
                'name' => 'Jane Doe',
            ],
        ], 200),
    ]);

    $gateway = new HttpCustomerContactGateway('https://customers.internal');
    $contact = $gateway->find($merchantId, $id);

    expect($contact)->not->toBeNull()
        ->and($contact->id)->toBe($id)
        ->and($contact->email)->toBe('jane@example.com')
        ->and($contact->name)->toBe('Jane Doe');
});

test('find returns null when the customer does not exist', function () {
    $merchantId = (string) Str::uuid();
    $id = (string) Str::uuid();
    Http::fake([
        "https://customers.internal/api/v1/merchants/{$merchantId}/customers/{$id}" => Http::response(['message' => 'not found'], 404),
    ]);

    $gateway = new HttpCustomerContactGateway('https://customers.internal');

    expect($gateway->find($merchantId, $id))->toBeNull();
});

test('find throws when customer-service responds with a server error', function () {
    $merchantId = (string) Str::uuid();
    $id = (string) Str::uuid();
    Http::fake([
        "https://customers.internal/api/v1/merchants/{$merchantId}/customers/{$id}" => Http::response(['message' => 'boom'], 500),
    ]);

    $gateway = new HttpCustomerContactGateway('https://customers.internal');

    $gateway->find($merchantId, $id);
})->throws(RequestException::class);
