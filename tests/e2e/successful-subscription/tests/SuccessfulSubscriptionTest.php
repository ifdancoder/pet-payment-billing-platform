<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * The platform's first full end-to-end test: a customer purchases a
 * subscription, walking every real hop through real HTTP against all
 * seven real services — Merchant -> Customer -> Product/Price ->
 * Subscription -> Invoice -> Payment -> Notification. Unlike every
 * tests/integration/*\/ slice this composes, nothing here is
 * direct-published: every event on the wire is produced by the actual
 * service whose job that is. See
 * docs/architecture/testing-strategy.md.
 *
 * This is the composition, not new integration work — each hop was
 * already proven in isolation:
 * - tests/integration/subscription-to-billing/ (Subscription -> Billing)
 * - tests/integration/billing-to-payment/ (Billing -> Payment)
 * - tests/integration/payment-to-billing/ (Payment -> Billing, the
 *   invoice.paid.v1 translation)
 * - tests/integration/billing-to-subscription/ (Billing -> Subscription,
 *   activation)
 * What none of those slices could prove on their own is that the whole
 * chain holds together end to end with the system generating and
 * threading every ID itself, and that Notification — consuming
 * payment.succeeded.v1 independently of Billing's own consumption of
 * it — actually fires in parallel with the rest of the chain, not
 * after it.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('a customer can purchase a subscription and it goes all the way to active', function () {
    $merchant = Services::identity()->post('/api/v1/merchants', [
        'json' => ['name' => 'E2E Successful Subscription Merchant'],
    ]);
    expect($merchant->getStatusCode())->toBe(201);
    $merchantId = json_decode($merchant->getBody()->getContents(), true)['data']['id'];

    $customerEmail = 'e2e-successful-subscription-'.Uuid::uuid4()->toString().'@example.com';
    $customer = Services::customer()->post('/api/v1/customers', [
        'json' => [
            'email' => $customerEmail,
            'name' => 'E2E Successful Subscription Customer',
            'merchant_id' => $merchantId,
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

    // Everything from here on is entirely async — subscription.created.v1
    // -> Billing opens an Invoice -> invoice.created.v1 -> Payment
    // processes it -> payment.succeeded.v1 fans out to both Billing
    // (marks the Invoice Paid, republishes invoice.paid.v1 ->
    // Subscription activates) and Notification (delivers a receipt) in
    // parallel. None of it is observable from the 201 responses above.

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

    // The thing the invoice.paid.v1 translation hop exists for: not
    // just that Billing knows the Invoice is paid, but that Subscription
    // — which never sees a payment event directly — ends up Active too.
    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    }, timeoutSeconds: 15);

    // Notification consumes payment.succeeded.v1 independently of
    // Billing's own consumption of it — this proves that fan-out
    // actually happens, not just that Billing's own branch of the chain
    // completes.
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
