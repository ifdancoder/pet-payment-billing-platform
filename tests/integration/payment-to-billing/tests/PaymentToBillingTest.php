<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * Service integration test, not an E2E: Billing and Payment doing the
 * things that are actually theirs to verify — Billing consuming
 * payment.succeeded.v1 and marking its Invoice Paid, republishing
 * invoice.paid.v1 (the hop that adds subscription_id back in for
 * Subscription, since Payment never carries it — see ADR 0002). Goes
 * one hop further than tests/integration/billing-to-payment/, which
 * stops once Payment succeeds. See
 * docs/architecture/testing-strategy.md.
 *
 * No subscription-service in this stack: the test seeds the one
 * Invoice it needs by publishing subscription.created.v1 directly
 * (Subscription's own Outbox is covered by
 * tests/integration/subscription-to-billing/), then lets the real
 * chain run the rest of the way — invoice.created.v1, the Payment, and
 * payment.succeeded.v1 are all produced by real billing-outbox/
 * payment-consumer/payment-outbox code, not simulated. Marking an
 * Invoice Paid needs one to already exist in billing's own database —
 * there's no way to fake that from outside without going through this
 * same chain.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('a real payment success eventually marks the invoice paid and republishes invoice.paid.v1', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('billing.events.v1', 'subscription.created.v1');
    $invoicePaidQueue = $amqp->bindTestQueue('invoice.paid.v1');

    $merchantId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, [
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => Uuid::uuid4()->toString(),
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 4999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    // Hop 1: billing-consumer creates the Invoice from the seed event
    // above.
    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('open');

        $invoiceId = $invoice[0]['id'];
    });

    // Hop 2: billing-outbox -> invoice.created.v1 -> payment-consumer
    // creates and processes a real Payment against it.
    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
    });

    // Hop 3, the thing this test actually exists to prove:
    // payment-outbox -> payment.succeeded.v1 -> billing-consumer marks
    // the same Invoice Paid.
    eventually(function () use ($merchantId, $invoiceId): void {
        $invoice = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices/{$invoiceId}");
        expect($invoice->getStatusCode())->toBe(200);

        $body = json_decode($invoice->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('paid');
        expect($body['paid_at'])->not->toBeNull();
        expect($body['payment_id'])->not->toBeNull();
    });

    // And not just Billing's own state — assert invoice.paid.v1 was
    // actually put back on the wire, with subscription_id restored
    // (the whole reason this translation hop exists: Payment's own
    // payment.succeeded.v1 never carries it). Reuses $amqp rather than
    // opening a fresh connection on every poll iteration.
    eventually(function () use ($amqp, $invoicePaidQueue, $subscriptionId, $invoiceId): void {
        $message = $amqp->readMessage($invoicePaidQueue);

        expect($message)->not->toBeNull();
        expect($message['aggregate_id'])->toBe($invoiceId);
        expect($message['payload']['subscription_id'])->toBe($subscriptionId);
        expect($message['payload']['invoice_id'])->toBe($invoiceId);
    });
});
