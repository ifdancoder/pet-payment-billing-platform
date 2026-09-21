<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\DockerCompose;
use Tests\Support\Services;

/**
 * Resilience test, not a business scenario: does billing-outbox
 * actually catch up on Outbox rows it missed while it was stopped —
 * the exact thing the Transactional Outbox pattern exists to make
 * true. The domain write (creating the Invoice) and the Outbox row
 * that describes it are committed together, synchronously, inside
 * CreateInvoiceHandler's own transaction — billing-outbox never has to
 * be running for that part to succeed. What this proves is the other
 * half of the pattern's promise: the relay being down doesn't lose the
 * row or need the original write replayed, it just catches up once
 * it's running again. See docs/architecture/testing-strategy.md.
 *
 * Reuses the billing-consumer/billing-outbox boundary
 * tests/integration/subscription-to-billing/ and
 * tests/integration/billing-to-payment/ already exercise — this is a
 * new question about an existing boundary, not a new one, so the test
 * seeds its Invoice the same way billing-to-payment does: publishing
 * subscription.created.v1 directly rather than pulling
 * subscription-service into the stack.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('billing-outbox catches up on rows it missed while it was stopped', function () {
    // Stopped *before* anything is created — the Outbox row this test
    // is actually about must be written while the relay provably isn't
    // running, not race a relay that just hasn't gotten to it yet.
    DockerCompose::stop('billing-outbox');

    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('billing.events.v1', 'subscription.created.v1');
    $invoiceCreatedQueue = $amqp->bindTestQueue('invoice.created.v1');

    $merchantId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, [
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => Uuid::uuid4()->toString(),
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 4200,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    // billing-consumer doesn't need billing-outbox running to do its
    // own job — CreateInvoiceHandler commits the Invoice and its
    // Outbox row in one transaction, entirely independent of whether
    // anything is currently relaying older rows.
    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);

        $invoiceId = $matching[0]['id'];
    });

    // No race to prove here — billing-outbox is confirmed stopped, so
    // there is no process that could have published this. A short
    // pause is just for clarity, not because the outcome is in doubt.
    sleep(1);
    expect($amqp->readMessage($invoiceCreatedQueue))->toBeNull();

    // The recovery: the same container, restarted — not a fresh one,
    // and not the original transaction replayed.
    DockerCompose::start('billing-outbox');

    eventually(function () use ($amqp, $invoiceCreatedQueue, $invoiceId): void {
        $message = $amqp->readMessage($invoiceCreatedQueue);

        expect($message)->not->toBeNull();
        expect($message['aggregate_id'])->toBe($invoiceId);
        expect($message['payload']['invoice_id'])->toBe($invoiceId);
    });
});
