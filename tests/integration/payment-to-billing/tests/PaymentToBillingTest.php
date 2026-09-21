<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('a real payment success eventually marks the invoice paid and republishes invoice.paid.v1', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('billing.events.v1', 'subscription.created.v1');
    $invoicePaidQueue = $amqp->bindTestQueue('invoice.paid.v1');

    $merchantId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    $amqp->publish('subscription.created.v1', 'subscription', $subscriptionId, [
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => Uuid::uuid4()->toString(),
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 4999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('open');

        $invoiceId = $invoice[0]['id'];
    });

    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
    });

    eventually(function () use ($merchantId, $invoiceId): void {
        $invoice = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices/{$invoiceId}");
        expect($invoice->getStatusCode())->toBe(200);

        $body = json_decode($invoice->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('paid');
        expect($body['paid_at'])->not->toBeNull();
        expect($body['payment_id'])->not->toBeNull();
    });

    eventually(function () use ($amqp, $invoicePaidQueue, $subscriptionId, $invoiceId): void {
        $message = $amqp->readMessage($invoicePaidQueue);

        expect($message)->not->toBeNull();
        expect($message['aggregate_id'])->toBe($invoiceId);
        expect($message['payload']['subscription_id'])->toBe($subscriptionId);
        expect($message['payload']['invoice_id'])->toBe($invoiceId);
    });
});
