<?php

use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * Resilience test, not a business scenario: does creating a
 * Subscription survive RabbitMQ being completely unreachable, and does
 * the chain catch up on its own once the broker comes back — without
 * the write being retried, replayed, or lost. See
 * docs/architecture/testing-strategy.md.
 *
 * The claim under test is architectural, not just "the outbox pattern
 * works": subscription-api's create flow (CreateSubscriptionHandler
 * and everything it depends on — the repository, the two HTTP
 * gateways, the Outbox port) never resolves AMQPChannel at all.
 * AppServiceProvider registers it as a lazy Laravel singleton, and only
 * PublishOutboxMessagesCommand and the *-events:consume commands ever
 * ask the container for one — so an HTTP request creating a
 * Subscription has no code path to RabbitMQ to fail on in the first
 * place, regardless of whether the broker is reachable.
 *
 * Reuses subscription-to-billing's exact stack and create flow — this
 * is a new question about an existing boundary (does it survive an
 * outage), not a new boundary.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
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

    // The outage. Everything above this point was setup; everything
    // below is the actual scenario.
    $docker->stop('rabbitmq');

    // The claim: this 201 has to come back Pending, exactly as it
    // would with the broker healthy — subscription-api never touches
    // RabbitMQ to answer this request.
    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionBody = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($subscriptionBody['status'])->toBe('pending');
    $subscriptionId = $subscriptionBody['id'];

    // No race to prove here — rabbitmq is confirmed stopped, so there
    // is no way subscription.created.v1 reached Billing yet.
    $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
    expect($invoices->getStatusCode())->toBe(200);
    expect(json_decode($invoices->getBody()->getContents(), true)['data'])->toBe([]);

    // The recovery: the same broker container, restarted — not a fresh
    // exchange, not the subscription-outbox/billing-consumer containers
    // replaced, and definitely not the HTTP request above retried.
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
