<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

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

    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, $payload, eventId: $eventId);

    // Allow several consumer polls before asserting that no duplicate appears.
    sleep(3);

    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);

    $body = json_decode($invoices->getBody()->getContents(), true)['data'];
    $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

    expect($matching)->toHaveCount(1);
    expect($matching[0]['id'])->toBe($firstInvoiceId);
});
