<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\LogWatcher;
use Tests\Support\Services;

/**
 * Resilience test, not a business scenario: does billing-consumer
 * actually survive a real crash between its DB commit and its AMQP
 * ack — not the same claim tests/resilience/duplicate-delivery/
 * already proved (that Inbox ignores an exact duplicate event_id,
 * however the duplicate arose), but that the *mechanism* which
 * produces that duplicate in production — a process dying with an
 * unacked message, RabbitMQ redelivering it once the connection drops
 * — actually triggers Inbox's guard for real, end to end. See
 * docs/architecture/testing-strategy.md.
 *
 * The crash is timed, not simulated: ConsumeBillingEventsCommand logs
 * a line after its handler commits and before it acks (see that
 * file's own comment), and — only in this stack —
 * CONSUMER_CRASH_TEST_DELAY_MS widens the gap between them from
 * microseconds to seconds, specifically so an external test can
 * observe the log line and kill the container before the ack, instead
 * of racing a gap no black-box test could otherwise hit reliably.
 *
 * Reuses the billing-consumer boundary duplicate-delivery and
 * subscription-to-billing already exercise — a new question about an
 * existing boundary, not a new one.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('billing-consumer surviving a real crash between commit and ack does not create a duplicate invoice', function () {
    $projectRoot = dirname(__DIR__);
    $logs = new LogWatcher($projectRoot);
    $docker = new DockerCompose($projectRoot);

    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('billing.events.v1', 'subscription.created.v1');

    $merchantId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    $eventId = $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, [
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => Uuid::uuid4()->toString(),
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 5000,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    // The crash. This line only appears after CreateInvoiceHandler's
    // transaction has committed — the Invoice already exists in
    // Postgres the moment this returns — and before $message->ack()
    // runs, thanks to CONSUMER_CRASH_TEST_DELAY_MS.
    $logs->waitForLine('billing-consumer', "Processed event {$eventId}, acking.");
    $docker->kill('billing-consumer');

    // Confirms the premise: the write really did commit before the
    // kill landed, not after it — this isn't just "eventually an
    // Invoice shows up," the timing claim above is directly checked.
    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);

        $invoiceId = $matching[0]['id'];
    }, timeoutSeconds: 15);

    // The recovery: the same container, restarted — not a fresh one.
    // RabbitMQ, having lost the connection that held this message
    // unacked, redelivers it; the restarted process's own
    // CreateInvoiceHandler call hits Inbox::recordIfNew() returning
    // false for the same event_id and no-ops rather than double-billing.
    $docker->start('billing-consumer');

    // Proving "still exactly one Invoice" after the redelivery is
    // proving an absence (no duplicate), the same deliberate sleep()
    // exception documented in
    // tests/integration/payment-to-notification/ and
    // tests/resilience/duplicate-delivery/ — eventually() is for
    // waiting on a condition to become true, not for confirming one
    // stays false. 10s covers container restart plus the redelivered
    // message paying the same CONSUMER_CRASH_TEST_DELAY_MS on its own
    // way through, with margin.
    sleep(10);

    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);

    $body = json_decode($invoices->getBody()->getContents(), true)['data'];
    $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

    expect($matching)->toHaveCount(1);
    expect($matching[0]['id'])->toBe($invoiceId);
});
