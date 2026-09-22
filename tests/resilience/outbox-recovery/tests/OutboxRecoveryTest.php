<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('billing-outbox catches up on rows it missed while it was stopped', function () {
    $docker = new DockerCompose(dirname(__DIR__));

    $docker->stop('billing-outbox');

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

    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);

        $invoiceId = $matching[0]['id'];
    });

    // The relay is stopped, so the event must remain unpublished during this window.
    sleep(1);
    expect($amqp->readMessage($invoiceCreatedQueue))->toBeNull();

    $docker->start('billing-outbox');

    eventually(function () use ($amqp, $invoiceCreatedQueue, $invoiceId): void {
        $message = $amqp->readMessage($invoiceCreatedQueue);

        expect($message)->not->toBeNull();
        expect($message['aggregate_id'])->toBe($invoiceId);
        expect($message['payload']['invoice_id'])->toBe($invoiceId);
    });
});
