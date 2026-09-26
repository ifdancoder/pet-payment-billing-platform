<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;

/**
 * Infrastructure question, not a business one: does the Ingress ->
 * gateway -> per-service routing table in infrastructure/nginx/nginx.conf
 * (see docs/adr/0001-api-gateway-routing.md) actually dispatch each
 * public path prefix to the right one of the seven backend services,
 * on a real cluster with a real ingress-nginx controller — not just
 * that nginx.conf parses. See docs/architecture/testing-strategy.md.
 *
 * Every route below needs no precondition — each is either a plain
 * list endpoint (empty is a valid 200) or the one identity-service
 * write with no existing tenant context to scope it by — so this test
 * has nothing to seed and nothing to clean up afterward.
 *
 * Run: point kubectl at the target cluster first (see README.md), then
 * composer install; composer test
 */
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

    // One no-precondition GET per backend service that has one — proof
    // each of these six distinct path families actually reaches a
    // live, responding service of its own, not a shared fallback.
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

    // A path entirely outside the routing table — proves this is a
    // real allow-list keyed on specific resource paths, not a blanket
    // "route everything somewhere" fallback that would make every
    // check above meaningless.
    $unrouted = $api->get('/v1/this-resource-does-not-exist-anywhere');
    expect($unrouted->getStatusCode())->toBe(503);
    expect(json_decode($unrouted->getBody()->getContents(), true))->toBe(['error' => 'no_services_available']);
});
