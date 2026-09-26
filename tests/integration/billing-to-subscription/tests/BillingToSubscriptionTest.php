<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

function createPendingSubscription(): array
{
    $merchantId = Uuid::uuid4()->toString();

    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => 'billing-to-subscription-'.Uuid::uuid4()->toString().'@example.com',
            'name' => 'Billing To Subscription Test Customer',
        ],
    ]);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'Billing To Subscription Test Plan'],
    ]);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 1500,
            'currency' => 'USD',
            'type' => 2, // recurring
            'billing_interval' => 3, // month
            'billing_interval_count' => 1,
        ],
    ]);
    $priceId = json_decode($price->getBody()->getContents(), true)['data']['id'];

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    $body = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($body['status'])->toBe('pending');

    return [
        'merchantId' => $merchantId,
        'customerId' => $customerId,
        'subscriptionId' => $body['id'],
    ];
}

function publishInvoicePaid(AmqpTestClient $amqp, string $merchantId, string $customerId, string $subscriptionId): void
{
    $invoiceId = Uuid::uuid4()->toString();

    $amqp->publish('invoice.paid.v1', 'invoice', $invoiceId, [
        'invoice_id' => $invoiceId,
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'subscription_id' => $subscriptionId,
        'payment_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 1500,
        'currency' => 'USD',
        'paid_at' => (new DateTimeImmutable)->format(DATE_ATOM),
    ]);
}

test('invoice.paid.v1 eventually activates a pending subscription', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('subscription.events.v1', 'invoice.paid.v1');

    ['merchantId' => $merchantId, 'customerId' => $customerId, 'subscriptionId' => $subscriptionId] = createPendingSubscription();

    publishInvoicePaid($amqp, $merchantId, $customerId, $subscriptionId);

    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    });
});

test('invoice.payment_failed.v1 eventually marks an active subscription past due', function () {
    $amqp = AmqpTestClient::fromEnv();
    $amqp->ensureConsumerQueueBound('subscription.events.v1', 'invoice.paid.v1');
    $amqp->ensureConsumerQueueBound('subscription.events.v1', 'invoice.payment_failed.v1');

    ['merchantId' => $merchantId, 'customerId' => $customerId, 'subscriptionId' => $subscriptionId] = createPendingSubscription();

    publishInvoicePaid($amqp, $merchantId, $customerId, $subscriptionId);
    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
    });

    $invoiceId = Uuid::uuid4()->toString();
    $amqp->publish('invoice.payment_failed.v1', 'invoice', $invoiceId, [
        'invoice_id' => $invoiceId,
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'subscription_id' => $subscriptionId,
        'payment_id' => Uuid::uuid4()->toString(),
        'failure_code' => 'card_declined',
    ]);

    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('past_due');
    });
});
