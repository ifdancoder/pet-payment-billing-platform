<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * Resilience test, not a business scenario: does Billing's Inbox
 * actually stop a redelivered subscription.created.v1 from creating a
 * second Invoice — not "does the code call recordIfNew()," but "does
 * the guarantee this whole platform's architecture rests on actually
 * hold against a real duplicate on the wire." See
 * docs/architecture/testing-strategy.md.
 *
 * At-least-once delivery means RabbitMQ *will* redeliver a message
 * whose ack it never saw — a consumer crashing after committing its
 * transaction but before acking, or an unacked message simply timing
 * out. Both produce the exact same thing on the wire: the same
 * event_id arriving twice. This test doesn't reproduce the crash
 * itself (that's a distinct scenario, not this one) — it reproduces
 * the one thing every such crash has in common, directly, by
 * publishing the identical event_id twice.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('a redelivered subscription.created.v1 does not create a second invoice', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('billing.events.v1', 'subscription.created.v1');

    $merchantId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();
    $eventId = Uuid::uuid4()->toString();

    $payload = [
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => Uuid::uuid4()->toString(),
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 3500,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ];

    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, $payload, eventId: $eventId);

    $firstInvoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$firstInvoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);

        $firstInvoiceId = $matching[0]['id'];
    });

    // The redelivery: identical event_id, identical payload — exactly
    // what RabbitMQ produces after a crash-before-ack, not a second,
    // independent event that happens to look the same.
    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, $payload, eventId: $eventId);

    // Proving a negative — no second Invoice appears — can't use
    // eventually() the normal way (it waits for a condition to become
    // *true*). This gives the wrong behavior a real window (several
    // actual billing-consumer loop iterations), then asserts on the
    // final state, the same deliberate exception to poll-don't-sleep
    // documented in tests/integration/payment-to-notification/.
    sleep(3);

    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);

    $body = json_decode($invoices->getBody()->getContents(), true)['data'];
    $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

    expect($matching)->toHaveCount(1);
    expect($matching[0]['id'])->toBe($firstInvoiceId);
});
