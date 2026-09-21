<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\LogWatcher;
use Tests\Support\Services;

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

    // The log is emitted after commit and before acknowledgement.
    $logs->waitForLine('billing-consumer', "Processed event {$eventId}, acking.");
    $docker->kill('billing-consumer');

    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);

        $invoiceId = $matching[0]['id'];
    }, timeoutSeconds: 15);

    $docker->start('billing-consumer');

    // The restarted consumer must process the redelivery before duplicate absence is asserted.
    sleep(10);

    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);

    $body = json_decode($invoices->getBody()->getContents(), true)['data'];
    $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

    expect($matching)->toHaveCount(1);
    expect($matching[0]['id'])->toBe($invoiceId);
});
