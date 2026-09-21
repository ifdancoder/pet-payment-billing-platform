<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\EventPublisher;
use Tests\Support\Services;

/**
 * Service integration test, not an E2E: exactly two services doing the
 * thing that's actually theirs to verify — Billing consuming
 * subscription.created.v1 and opening an Invoice, then publishing
 * invoice.created.v1 through a real Outbox and a real RabbitMQ, Payment
 * consuming it and auto-processing a Payment. See
 * docs/architecture/testing-strategy.md.
 *
 * subscription-service isn't part of this stack — this test publishes
 * subscription.created.v1 directly (see Support\EventPublisher), since
 * Subscription actually publishing it correctly is already covered by
 * tests/integration/subscription-to-billing/.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('billing opening an invoice eventually produces a succeeded payment', function () {
    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    EventPublisher::publishSubscriptionCreated([
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 2999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    // First hop: billing-consumer picks the message up on its own
    // polling loop and creates the Invoice — not observable from the
    // publish call above, which only proves the message reached
    // RabbitMQ.
    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('open');
        expect($invoice[0]['total_amount_minor_units'])->toBe(2999);

        $invoiceId = $invoice[0]['id'];
    });

    // Second hop: billing-outbox relays invoice.created.v1, and
    // payment-consumer auto-creates and processes a Payment against it
    // (the fake payment gateway always succeeds today).
    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
        expect($payment[0]['amount_minor_units'])->toBe(2999);
        expect($payment[0]['currency'])->toBe('USD');
    });
});
