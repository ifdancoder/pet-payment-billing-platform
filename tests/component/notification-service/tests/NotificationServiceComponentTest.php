<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

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

        expect($body)->toHaveCount(1);

        $notification = $body[0];
        expect($notification['source_event_id'])->toBe($eventId);
        expect($notification['type'])->toBe('payment_receipt');
        expect($notification['channel'])->toBe('email');

        expect($notification['recipient'])->toBe('component-test-customer@example.com');
        expect($notification['status'])->toBe('sent');
    }, timeoutSeconds: 15);
});

test('payment.succeeded.v1 for an unknown customer never creates a notification, per the stub', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('notification.payment-succeeded', 'payment.succeeded.v1');

    $merchantId = Uuid::uuid4()->toString();

    publishPaymentSucceeded($amqp, $merchantId, '00000000-0000-0000-0000-000000000404');

    // Allow several worker polls before asserting that no row appears.
    sleep(3);

    $response = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($response->getStatusCode())->toBe(200);
    expect(json_decode($response->getBody()->getContents(), true)['data'])->toBe([]);
});
