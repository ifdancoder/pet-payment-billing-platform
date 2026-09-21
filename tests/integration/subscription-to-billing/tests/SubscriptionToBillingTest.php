<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * Service integration test, not an E2E: exactly two services doing the
 * thing that's actually theirs to verify — Subscription publishing
 * subscription.created.v1 through a real Outbox and a real RabbitMQ,
 * Billing consuming it and opening an Invoice — plus subscription's two
 * synchronous dependencies (customer/catalog) needed just to create a
 * subscription at all. See docs/architecture/testing-strategy.md.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('creating a subscription eventually opens an invoice in Billing', function () {
    $merchantId = Uuid::uuid4()->toString();

    $customer = Services::customer()->post('/api/v1/customers', [
        'json' => [
            'email' => "svc-integration-{$merchantId}@example.com",
            'name' => 'Service Integration Test Customer',
            'merchant_id' => $merchantId,
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'Service Integration Test Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 1999,
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

    // The chain from here on is entirely async: subscription-api writes
    // the domain row and an Outbox row in one transaction and returns;
    // subscription-outbox picks the Outbox row up on its own loop and
    // publishes to RabbitMQ; billing-consumer picks the message up on
    // its own loop, and only then does the Invoice exist. None of that
    // is observable from the 201 response above.
    eventually(function () use ($merchantId, $subscriptionId, $priceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('open');
        expect($invoice[0]['currency'])->toBe('USD');
        expect($invoice[0]['total_amount_minor_units'])->toBe(1999);
        expect($invoice[0]['lines'][0]['price_id'])->toBe($priceId);
    });
});
