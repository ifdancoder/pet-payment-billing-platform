<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('a declined charge leaves the invoice open, the payment failed, and the subscription pending', function () {
    $registration = Services::identity()->post('/api/v1/auth/register', [
        'json' => [
            'email' => 'owner-failed-'.Uuid::uuid4()->toString().'@example.com',
            'password' => 'correct horse battery staple',
            'merchant_name' => 'E2E Failed Payment Merchant',
        ],
    ]);
    expect($registration->getStatusCode())->toBe(201);
    $registrationBody = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $registrationBody['merchant_id'];
    Services::authenticate($registrationBody['access_token']);

    $customerEmail = 'e2e-failed-payment-'.Uuid::uuid4()->toString().'@example.com';
    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => $customerEmail,
            'name' => 'E2E Failed Payment Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'E2E Failed Payment Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 66660000,
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
        $invoiceId = $invoice[0]['id'];
    }, timeoutSeconds: 15);

    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('failed');
        expect($payment[0]['attempts'])->toHaveCount(1);
        expect($payment[0]['attempts'][0]['failure_code'])->toBe('card_declined');
    }, timeoutSeconds: 15);

    // Negative assertions need a window in which downstream consumers can run.
    sleep(5);

    $invoice = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices/{$invoiceId}");
    expect($invoice->getStatusCode())->toBe(200);
    expect(json_decode($invoice->getBody()->getContents(), true)['data']['status'])->toBe('open');

    $subscription = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
    expect($subscription->getStatusCode())->toBe(200);
    expect(json_decode($subscription->getBody()->getContents(), true)['data']['status'])->toBe('pending');

    $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($notifications->getStatusCode())->toBe(200);
    $body = json_decode($notifications->getBody()->getContents(), true)['data'];
    $receipt = array_values(array_filter($body, fn (array $n) => $n['recipient'] === $customerEmail));
    expect($receipt)->toBeEmpty();
});
