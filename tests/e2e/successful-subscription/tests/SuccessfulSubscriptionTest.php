<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('a customer can purchase a subscription and it goes all the way to active', function () {
    $registration = Services::identity()->post('/api/v1/auth/register', [
        'json' => [
            'email' => 'owner-success-'.Uuid::uuid4()->toString().'@example.com',
            'password' => 'correct horse battery staple',
            'merchant_name' => 'E2E Successful Subscription Merchant',
        ],
    ]);
    expect($registration->getStatusCode())->toBe(201);
    $registrationBody = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $registrationBody['merchant_id'];
    Services::authenticate($registrationBody['access_token']);

    $customerEmail = 'e2e-successful-subscription-'.Uuid::uuid4()->toString().'@example.com';
    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => $customerEmail,
            'name' => 'E2E Successful Subscription Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'E2E Successful Subscription Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
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

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionBody = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($subscriptionBody['status'])->toBe('pending');
    $subscriptionId = $subscriptionBody['id'];

    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('paid');
        expect($invoice[0]['total_amount_minor_units'])->toBe(2500);

        $invoiceId = $invoice[0]['id'];
    }, timeoutSeconds: 15);

    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
    });

    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    }, timeoutSeconds: 15);

    eventually(function () use ($merchantId, $customerEmail): void {
        $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
        expect($notifications->getStatusCode())->toBe(200);

        $body = json_decode($notifications->getBody()->getContents(), true)['data'];
        $receipt = array_values(array_filter($body, fn (array $n) => $n['recipient'] === $customerEmail));

        expect($receipt)->toHaveCount(1);
        expect($receipt[0]['type'])->toBe('payment_receipt');
        expect($receipt[0]['status'])->toBe('sent');
    }, timeoutSeconds: 15);
});
