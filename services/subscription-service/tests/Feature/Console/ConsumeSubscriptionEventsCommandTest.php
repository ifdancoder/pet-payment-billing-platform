<?php

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

function aQueuedSubscriptionEventsMessage(string $body, string $routingKey): AMQPMessage
{
    $message = new AMQPMessage($body, [
        'application_headers' => new AMQPTable([
            'event_id' => (string) Str::uuid(),
            'occurred_at' => (new DateTimeImmutable)->format(DATE_ATOM),
        ]),
    ]);
    $deliveryChannel = Mockery::mock(AMQPChannel::class);
    $deliveryChannel->shouldReceive('basic_ack')->once()->with(1, false);
    $message->setChannel($deliveryChannel);
    $message->setDeliveryInfo(1, false, 'billing.events', $routingKey);

    return $message;
}

function seedSubscriptionForConsumeEventsCommand(MerchantId $merchantId, SubscriptionStatus $status): Subscription
{
    $subscription = Subscription::reconstitute(
        SubscriptionId::generate(),
        $merchantId,
        CustomerId::generate(),
        PriceSnapshot::of(PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD), BillingPeriod::of(BillingInterval::Month, 1)),
        $status,
    );
    (new EloquentSubscriptionRepository(new SubscriptionMapper))->save($subscription);

    return $subscription;
}

test('it declares the queue with bindings for every event type it consumes', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once()->with('subscription.events.v1', false, true, false, false);
    $channel->shouldReceive('queue_bind')->once()->with('subscription.events.v1', 'billing.events', 'invoice.paid.v1');
    $channel->shouldReceive('queue_bind')->once()->with('subscription.events.v1', 'billing.events', 'invoice.payment_failed.v1');
    $channel->shouldReceive('basic_get')->once()->with('subscription.events.v1')->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-events:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});

test('it routes an invoice.paid.v1 message to the activation flow', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForConsumeEventsCommand($merchantId, SubscriptionStatus::Pending);
    $body = json_encode([
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'subscription_id' => $subscription->id()->toString(),
        'payment_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
    ]);
    $message = aQueuedSubscriptionEventsMessage($body, 'invoice.paid.v1');
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-events:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::Active);
});

test('it routes an invoice.payment_failed.v1 message to the past-due flow', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForConsumeEventsCommand($merchantId, SubscriptionStatus::Active);
    $body = json_encode([
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'subscription_id' => $subscription->id()->toString(),
        'payment_id' => (string) Str::uuid(),
        'failure_code' => 'card_declined',
    ]);
    $message = aQueuedSubscriptionEventsMessage($body, 'invoice.payment_failed.v1');
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-events:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::PastDue);
});

test('it reports zero when there is nothing to consume', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-events:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});
