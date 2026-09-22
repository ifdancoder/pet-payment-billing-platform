<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('creating a subscription succeeds against stubbed customer/catalog services and publishes subscription.created.v1', function () {
    $amqp = AmqpTestClient::fromEnv();
    $queue = $amqp->bindTestQueue('subscription.created.v1');

    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();
    $priceId = Uuid::uuid4()->toString();

    $response = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);

    expect($response->getStatusCode())->toBe(201);
    $body = json_decode($response->getBody()->getContents(), true)['data'];
    expect($body['status'])->toBe('pending');
    $subscriptionId = $body['id'];

    eventually(function () use ($amqp, $queue, $subscriptionId, $merchantId, $customerId, $priceId): void {
        $message = $amqp->readMessage($queue);

        expect($message)->not->toBeNull();
        expect($message['aggregate_id'])->toBe($subscriptionId);
        expect($message['payload']['merchant_id'])->toBe($merchantId);
        expect($message['payload']['customer_id'])->toBe($customerId);
        expect($message['payload']['price_id'])->toBe($priceId);

        expect($message['payload']['amount_minor_units'])->toBe(2500);
        expect($message['payload']['currency'])->toBe('USD');
    });
});

test('creating a subscription for an unknown customer fails with 404, per the stub', function () {
    $merchantId = Uuid::uuid4()->toString();

    $response = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => [
            'customer_id' => '00000000-0000-0000-0000-000000000404',
            'price_id' => Uuid::uuid4()->toString(),
        ],
    ]);

    expect($response->getStatusCode())->toBe(404);
});

test('invoice.paid.v1 eventually activates a pending subscription, with no billing-service in this stack at all', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('subscription.events.v1', 'invoice.paid.v1');

    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => Uuid::uuid4()->toString()],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionId = json_decode($subscription->getBody()->getContents(), true)['data']['id'];

    $invoiceId = Uuid::uuid4()->toString();
    $amqp->publish('invoice.paid.v1', 'invoice', $invoiceId, [
        'invoice_id' => $invoiceId,
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'subscription_id' => $subscriptionId,
        'payment_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 2500,
        'currency' => 'USD',
        'paid_at' => (new DateTimeImmutable)->format(DATE_ATOM),
    ]);

    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    });
});
