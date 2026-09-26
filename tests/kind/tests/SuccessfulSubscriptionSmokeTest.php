<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;

/**
 * The one business smoke scenario ADR 0004 calls for here — not the
 * full E2E catalog (that's tests/e2e/, on Docker Compose). Same golden
 * path as tests/e2e/successful-subscription/, but walked entirely
 * through the real public API on a real cluster: real Ingress, real
 * gateway, real Kubernetes Services/DNS between all seven services.
 * NetworkPolicies and HPA are deliberately outside this local kind
 * proof — see infrastructure/kubernetes/overlays/local/kustomization.yaml's
 * own comment on why both are skipped locally. The point
 * isn't re-proving the business logic tests/e2e/successful-subscription/
 * already covers — it's proving the platform's Kubernetes wiring
 * (Services, Deployments, migrate Jobs, the gateway routing table)
 * doesn't get in the way of it actually working end to end.
 *
 * Unlike every Docker Compose stack in tests/, this cluster is shared,
 * long-lived state — nothing here tears anything down. Every ID is
 * freshly generated, so repeated runs never collide with data from a
 * previous one.
 *
 * Run: point kubectl at the target cluster first (see README.md), then
 * composer install; composer test
 */
test('a customer can purchase a subscription through the real Ingress and it goes all the way to active', function () {
    $api = Api::client();

    $registered = $api->post('/v1/auth/register', [
        'json' => [
            'email' => 'kind-smoke-'.Uuid::uuid4()->toString().'@example.com',
            'password' => 'correct horse battery staple',
            'merchant_name' => 'kind smoke: successful subscription',
        ],
    ]);
    expect($registered->getStatusCode())->toBe(201);
    $account = json_decode($registered->getBody()->getContents(), true);
    $merchantId = $account['merchant_id'];
    Api::authenticate($account['access_token']);
    $api = Api::client();

    $customerEmail = 'kind-smoke-'.Uuid::uuid4()->toString().'@example.com';
    $customer = $api->post("/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => $customerEmail,
            'name' => 'kind Smoke Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = $api->post("/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'kind Smoke Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = $api->post("/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 2500,
            'currency' => 'USD',
            'type' => 2, // recurring
            'billing_interval' => 3, // month
            'billing_interval_count' => 1,
        ],
    ]);
    expect($price->getStatusCode())->toBe(201);
    $priceId = json_decode($price->getBody()->getContents(), true)['data']['id'];

    $subscription = $api->post("/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionBody = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($subscriptionBody['status'])->toBe('pending');
    $subscriptionId = $subscriptionBody['id'];

    // Everything from here on is async across pods, exactly like
    // tests/e2e/successful-subscription/ — the only difference is
    // every hop crosses a real Kubernetes Service, not a Docker
    // Compose network alias.
    $invoiceId = null;
    eventually(function () use ($api, $merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = $api->get("/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('paid');

        $invoiceId = $invoice[0]['id'];
    }, timeoutSeconds: 20);

    eventually(function () use ($api, $merchantId, $invoiceId): void {
        $payments = $api->get("/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
    }, timeoutSeconds: 20);

    eventually(function () use ($api, $merchantId, $subscriptionId): void {
        $response = $api->get("/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    }, timeoutSeconds: 20);

    eventually(function () use ($api, $merchantId, $customerEmail): void {
        $notifications = $api->get("/v1/merchants/{$merchantId}/notifications");
        expect($notifications->getStatusCode())->toBe(200);

        $body = json_decode($notifications->getBody()->getContents(), true)['data'];
        $receipt = array_values(array_filter($body, fn (array $n) => $n['recipient'] === $customerEmail));

        expect($receipt)->toHaveCount(1);
        expect($receipt[0]['type'])->toBe('payment_receipt');
        expect($receipt[0]['status'])->toBe('sent');
    }, timeoutSeconds: 20);
});
