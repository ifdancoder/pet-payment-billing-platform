<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

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
        'paid_at' => (new DateTimeImmutable)->format(DATE_ATOM),
    ]);

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
        'paid_at' => (new DateTimeImmutable)->format(DATE_ATOM),
    ]);

    // Allow several worker polls before asserting that no row appears.
    sleep(3);

    $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($notifications->getStatusCode())->toBe(200);
    expect(json_decode($notifications->getBody()->getContents(), true)['data'])->toBe([]);
});
