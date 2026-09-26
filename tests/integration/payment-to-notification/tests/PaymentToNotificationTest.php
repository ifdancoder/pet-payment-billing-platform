<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * Service integration test, not an E2E: Notification doing what's
 * actually theirs to verify — consuming payment.succeeded.v1
 * independently of Billing's own consumption of the same event,
 * looking up the customer's email over HTTP, and delivering a receipt.
 * See docs/architecture/testing-strategy.md.
 *
 * No payment-service or billing-service in this stack: Payment
 * publishing payment.succeeded.v1 correctly is already covered by
 * tests/integration/billing-to-payment/, so this test publishes it
 * directly, standing in for Payment's own Outbox. customer-service
 * *is* here — PaymentSucceededConsumer looks the customer up
 * synchronously to get an email address, so it's a real dependency of
 * this boundary, not the boundary itself.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('payment.succeeded.v1 eventually delivers an email receipt', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('notification.payment-succeeded', 'payment.succeeded.v1');

    $merchantId = Uuid::uuid4()->toString();
    $customerEmail = 'payment-to-notification-'.Uuid::uuid4()->toString().'@example.com';

    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => $customerEmail,
            'name' => 'Payment To Notification Test Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $paymentId = Uuid::uuid4()->toString();
    $amqp->publish('payment.succeeded.v1', 'payment', $paymentId, [
        'payment_id' => $paymentId,
        'invoice_id' => Uuid::uuid4()->toString(),
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'amount_minor_units' => 999,
        'currency' => 'USD',
        'paid_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ]);

    // Two async hops: notification-ingest-consumer creates a Pending
    // Notification from the event above, then notification-delivery-worker
    // — a *different* consumer, of the Notification row itself, not of
    // a RabbitMQ event — turns it into Sent.
    eventually(function () use ($merchantId, $customerEmail): void {
        $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
        expect($notifications->getStatusCode())->toBe(200);

        $body = json_decode($notifications->getBody()->getContents(), true)['data'];
        $receipt = array_values(array_filter($body, fn (array $n) => $n['recipient'] === $customerEmail));

        expect($receipt)->toHaveCount(1);
        expect($receipt[0]['type'])->toBe('payment_receipt');
        expect($receipt[0]['channel'])->toBe('email');
        expect($receipt[0]['status'])->toBe('sent');
    });
});

test('payment.succeeded.v1 for an unknown customer never creates a notification', function () {
    // PaymentSucceededConsumer's own guard: no contact to notify (e.g.
    // the customer was deleted, or customer-service is unreachable)
    // means neither the Inbox nor a Notification gets recorded, so a
    // redelivery of the same event can simply retry the lookup later.
    // Proving a negative can't use eventually() the normal way — it
    // waits for a condition to become true — so this gives the wrong
    // behavior a real window to show up (several worker loop
    // iterations), then asserts it didn't.
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('notification.payment-succeeded', 'payment.succeeded.v1');

    $merchantId = Uuid::uuid4()->toString();
    $unknownCustomerId = Uuid::uuid4()->toString();
    $paymentId = Uuid::uuid4()->toString();

    $amqp->publish('payment.succeeded.v1', 'payment', $paymentId, [
        'payment_id' => $paymentId,
        'invoice_id' => Uuid::uuid4()->toString(),
        'merchant_id' => $merchantId,
        'customer_id' => $unknownCustomerId,
        'amount_minor_units' => 999,
        'currency' => 'USD',
        'paid_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ]);

    sleep(3);

    $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($notifications->getStatusCode())->toBe(200);
    expect(json_decode($notifications->getBody()->getContents(), true)['data'])->toBe([]);
});
