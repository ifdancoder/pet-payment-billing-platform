<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;

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
