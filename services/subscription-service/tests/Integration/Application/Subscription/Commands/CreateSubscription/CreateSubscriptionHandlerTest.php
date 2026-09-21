<?php

use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionHandler;
use App\Application\Subscription\Exceptions\CustomerNotFound;
use App\Application\Subscription\Exceptions\PriceIsNotActive;
use App\Application\Subscription\Exceptions\PriceIsNotRecurring;
use App\Application\Subscription\Exceptions\PriceNotFound;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;

test('handle persists a new pending subscription when the customer and price both exist', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    $productId = '11111111-1111-4111-8111-111111111111';

    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
                'product_id' => $productId,
                'amount_minor_units' => 1999,
                'currency' => 'USD',
                'type' => 'recurring',
                'billing_interval' => 'month',
                'billing_interval_count' => 1,
                'status' => 'active',
            ],
        ], 200),
    ]);

    $subscription = app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        $priceId->toString(),
    ));

    expect($subscription->merchantId()->equals($merchantId))->toBeTrue()
        ->and($subscription->customerId()->equals($customerId))->toBeTrue()
        ->and($subscription->priceSnapshot()->priceId()->toString())->toBe($priceId->toString())
        ->and($subscription->priceSnapshot()->productId()->toString())->toBe($productId)
        ->and($subscription->status())->toBe(SubscriptionStatus::Pending);
});

test('handle throws CustomerNotFound when the customer does not exist', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        PriceId::generate()->toString(),
    ));
})->throws(CustomerNotFound::class);

test('handle throws PriceNotFound when the price does not exist for the merchant', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response(['message' => 'not found'], 404),
    ]);

    app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        $priceId->toString(),
    ));
})->throws(PriceNotFound::class);

test('handle throws PriceIsNotRecurring when the price is a one-time price', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
                'product_id' => '11111111-1111-4111-8111-111111111111',
                'amount_minor_units' => 4999,
                'currency' => 'USD',
                'type' => 'one_time',
                'billing_interval' => null,
                'billing_interval_count' => null,
                'status' => 'active',
            ],
        ], 200),
    ]);

    app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        $priceId->toString(),
    ));
})->throws(PriceIsNotRecurring::class);

test('handle throws PriceIsNotActive when the price is inactive', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
                'product_id' => '11111111-1111-4111-8111-111111111111',
                'amount_minor_units' => 1999,
                'currency' => 'USD',
                'type' => 'recurring',
                'billing_interval' => 'month',
                'billing_interval_count' => 1,
                'status' => 'inactive',
            ],
        ], 200),
    ]);

    app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        $priceId->toString(),
    ));
})->throws(PriceIsNotActive::class);

test('handle records a SubscriptionCreated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    Http::fake([
        "*/api/v1/customers/{$customerId->toString()}" => Http::response(['data' => ['id' => $customerId->toString(), 'email' => 'jane@example.com', 'name' => 'Jane']], 200),
        "*/api/v1/merchants/{$merchantId->toString()}/prices/{$priceId->toString()}" => Http::response([
            'data' => [
                'id' => $priceId->toString(),
                'product_id' => '11111111-1111-4111-8111-111111111111',
                'amount_minor_units' => 1999,
                'currency' => 'USD',
                'type' => 'recurring',
                'billing_interval' => 'month',
                'billing_interval_count' => 1,
                'status' => 'active',
            ],
        ], 200),
    ]);

    $subscription = app(CreateSubscriptionHandler::class)->handle(new CreateSubscriptionCommand(
        $merchantId->toString(),
        $customerId->toString(),
        $priceId->toString(),
    ));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $created = collect($unpublished)->firstWhere('eventType', 'subscription.created.v1');
    expect($created)->not->toBeNull()
        ->and($created->aggregateId)->toBe($subscription->id()->toString())
        ->and($created->payload['amount_minor_units'])->toBe(1999);
});
