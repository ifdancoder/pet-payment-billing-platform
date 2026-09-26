<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;

test('the Ingress routes each public path prefix to its own backend service', function () {
    $api = Api::client();
    $registration = $api->post('/v1/auth/register', ['json' => [
        'email' => 'kind-routing-'.Uuid::uuid4()->toString().'@example.com',
        'password' => 'correct horse battery staple',
        'merchant_name' => 'kind smoke: ingress routing',
    ]]);
    expect($registration->getStatusCode())->toBe(201);
    $account = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $account['merchant_id'];
    Api::authenticate($account['access_token']);
    $api = Api::client();

    $routes = [
        "/v1/merchants/{$merchantId}/products" => 'catalog-service',
        "/v1/merchants/{$merchantId}/subscriptions" => 'subscription-service',
        "/v1/merchants/{$merchantId}/invoices" => 'billing-service',
        "/v1/merchants/{$merchantId}/payments" => 'payment-service',
        "/v1/merchants/{$merchantId}/notifications" => 'notification-service',
        "/v1/merchants/{$merchantId}/customers" => 'customer-service',
    ];

    foreach ($routes as $path => $expectedBackend) {
        $response = $api->get($path);
        expect($response->getStatusCode())->toBe(200, "GET {$path} (expected to reach {$expectedBackend})");
        expect(json_decode($response->getBody()->getContents(), true))->toHaveKey('data');
    }

    $unrouted = $api->get('/v1/this-resource-does-not-exist-anywhere');
    expect($unrouted->getStatusCode())->toBe(503);
    expect(json_decode($unrouted->getBody()->getContents(), true))->toBe(['error' => 'no_services_available']);
});
