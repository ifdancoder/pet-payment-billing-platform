<?php

use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('creating a subscription survives a rabbitmq outage and the chain catches up once it recovers', function () {
    $docker = new DockerCompose(dirname(__DIR__));

    $merchantId = Uuid::uuid4()->toString();

    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => 'rabbitmq-outage-'.Uuid::uuid4()->toString().'@example.com',
            'name' => 'RabbitMQ Outage Test Customer',
        ],
    ]);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'RabbitMQ Outage Test Plan'],
    ]);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 2000,
            'currency' => 'USD',
            'type' => 2, // recurring
            'billing_interval' => 3, // month
            'billing_interval_count' => 1,
        ],
    ]);
    $priceId = json_decode($price->getBody()->getContents(), true)['data']['id'];

    $docker->stop('rabbitmq');

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionBody = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($subscriptionBody['status'])->toBe('pending');
    $subscriptionId = $subscriptionBody['id'];

    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);
    expect(json_decode($invoices->getBody()->getContents(), true)['data'])->toBe([]);

    $docker->start('rabbitmq');

    eventually(function () use ($merchantId, $subscriptionId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $matching = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($matching)->toHaveCount(1);
        expect($matching[0]['status'])->toBe('open');
    }, timeoutSeconds: 20);
});
