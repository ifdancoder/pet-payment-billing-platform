<?php

use BillingPlatform\TestSupport\AmqpTestClient;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * The platform's first Component test: subscription-service alone, as
 * its own live process — real HTTP server, real Postgres, real
 * RabbitMQ — with a WireMock stub standing in for its two synchronous
 * HTTP dependencies (customer-service, catalog-service) instead of the
 * real services. See docs/architecture/testing-strategy.md.
 *
 * Different question from tests/integration/subscription-to-billing/,
 * which proves this same create flow works with the *real*
 * customer-service and catalog-service in the loop, and a real
 * billing-service actually consuming subscription.created.v1: this
 * test asks whether subscription-service's own container is correct
 * in isolation — its HTTP API, its Outbox/RabbitMQ publish path, and
 * its own RabbitMQ consume path — without paying for two-plus extra
 * services just to answer that. Guard conditions that would be slow or
 * awkward to set up against a real customer-service (an unknown
 * customer, specifically) are trivial against a stub instead.
 *
 * billing-service publishing invoice.paid.v1/invoice.payment_failed.v1
 * correctly is already covered by
 * tests/integration/payment-to-billing/, so the consume-side test here
 * publishes directly, the same reasoning every tests/integration/*\/
 * slice uses for an upstream event it isn't the one under test.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('creating a subscription succeeds against stubbed customer/catalog services and publishes subscription.created.v1', function () {
    $amqp = AmqpTestClient::fromEnv();
    $queue = $amqp->bindTestQueue('subscription.created.v1');

    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();
    $priceId = Uuid::uuid4()->toString();

    // customer-catalog-stub's wildcard mappings answer any customer_id
    // and price_id with a fixed customer/price, echoing the requested
    // id back — see wiremock/mappings/{customer,price}-found.json.
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
        // The stub's fixed price data (wiremock/mappings/price-found.json),
        // proving the response actually got parsed and threaded through,
        // not just that some request to the stub succeeded.
        expect($message['payload']['amount_minor_units'])->toBe(2500);
        expect($message['payload']['currency'])->toBe('USD');
    });
});

test('creating a subscription for an unknown customer fails with 404, per the stub', function () {
    $merchantId = Uuid::uuid4()->toString();

    // wiremock/mappings/customer-not-found.json: this one fixed id is
    // the only one the stub answers 404 for. Trivial to arrange here;
    // would mean provisioning a genuinely missing customer against a
    // real customer-service in a service integration test instead.
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
