<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * The platform's second Component test: notification-service alone, as
 * its own live process — real HTTP server, real Postgres, real
 * RabbitMQ — with a WireMock stub standing in for its one synchronous
 * HTTP dependency (customer-service) instead of the real service. See
 * docs/architecture/testing-strategy.md and
 * tests/component/subscription-service/ for the first slice this one
 * follows.
 *
 * notification-service publishes nothing (see the event catalog), so
 * unlike subscription-service's Component test there's no Outbox/publish
 * side to exercise here — only the RabbitMQ consume path
 * (payment.succeeded.v1, published directly, standing in for
 * payment-service, which isn't part of this stack) and the delivery
 * worker, which needs no stub of its own since FakeEmailSender is an
 * in-process fake, not an HTTP call.
 *
 * payment-service publishing payment.succeeded.v1 correctly is already
 * covered by tests/integration/billing-to-payment/, and this exact
 * consumer's own "no contact, no notification" guard is already
 * covered by tests/integration/payment-to-notification/ against a
 * *real* customer-service. What this test adds is the same guard
 * proven against notification-service alone, with the same cheap stub
 * arrangement tests/component/subscription-service/ used for its own
 * unknown-customer case — one fixed sentinel id instead of real broken
 * state.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
/**
 * Returns the published event_id, not the payment_id — Notification's
 * own sourceEventId is the RabbitMQ event_id (see
 * ConsumePaymentSucceededCommand -> CreateNotificationCommand), so
 * that's the one worth asserting on downstream.
 */
function publishPaymentSucceeded(AmqpTestClient $amqp, string $merchantId, string $customerId): string
{
    $paymentId = Uuid::uuid4()->toString();

    return $amqp->publish('payment.succeeded.v1', 'payment', $paymentId, [
        'payment_id' => $paymentId,
        'invoice_id' => Uuid::uuid4()->toString(),
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'amount_minor_units' => 2500,
        'currency' => 'USD',
        'paid_at' => (new DateTimeImmutable)->format(DATE_ATOM),
    ]);
}

test('payment.succeeded.v1 eventually delivers an email receipt, sourced from the stubbed customer', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('notification.payment-succeeded', 'payment.succeeded.v1');

    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();

    $eventId = publishPaymentSucceeded($amqp, $merchantId, $customerId);

    eventually(function () use ($merchantId, $eventId): void {
        $response = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        // Exactly one notification for this merchant — this stack only
        // ever sees the one event this test published.
        expect($body)->toHaveCount(1);

        $notification = $body[0];
        expect($notification['source_event_id'])->toBe($eventId);
        expect($notification['type'])->toBe('payment_receipt');
        expect($notification['channel'])->toBe('email');
        // The stub's fixed email (wiremock/mappings/customer-found.json)
        // — proving the lookup response actually got parsed and
        // threaded through, not just that some request succeeded.
        expect($notification['recipient'])->toBe('component-test-customer@example.com');
        expect($notification['status'])->toBe('sent');
    }, timeoutSeconds: 15);
});

test('payment.succeeded.v1 for an unknown customer never creates a notification, per the stub', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('notification.payment-succeeded', 'payment.succeeded.v1');

    $merchantId = Uuid::uuid4()->toString();

    // wiremock/mappings/customer-not-found.json: this one fixed id is
    // the only one the stub answers 404 for.
    publishPaymentSucceeded($amqp, $merchantId, '00000000-0000-0000-0000-000000000404');

    // Proving an absence, not "not yet" — eventually() is for waiting
    // on a condition to become true. Same deliberate sleep()-over-
    // eventually() exception documented in
    // tests/integration/payment-to-notification/: several real worker
    // loop iterations, not a race against the assertion below.
    sleep(3);

    $response = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($response->getStatusCode())->toBe(200);
    expect(json_decode($response->getBody()->getContents(), true)['data'])->toBe([]);
});
