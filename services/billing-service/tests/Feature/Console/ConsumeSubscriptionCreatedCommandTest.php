<?php

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

test('it consumes and acks one pending message and reports it', function () {
    $merchantId = MerchantId::generate()->toString();
    $body = json_encode([
        'subscription_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId,
        'customer_id' => (string) Str::uuid(),
        'price_id' => (string) Str::uuid(),
        'product_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);
    $message = new AMQPMessage($body, [
        'application_headers' => new AMQPTable([
            'event_id' => (string) Str::uuid(),
            'occurred_at' => (new DateTimeImmutable)->format(DATE_ATOM),
        ]),
    ]);
    $deliveryChannel = Mockery::mock(AMQPChannel::class);
    $deliveryChannel->shouldReceive('basic_ack')->once()->with(1, false);
    $message->setChannel($deliveryChannel);
    $message->setDeliveryInfo(1, false, 'billing.events', 'subscription.created.v1');

    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once()->with('billing.subscription-created', false, true, false, false);
    $channel->shouldReceive('queue_bind')->once()->with('billing.subscription-created', 'billing.events', 'subscription.created.v1');
    $channel->shouldReceive('basic_get')->once()->with('billing.subscription-created')->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-created:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    expect(app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($merchantId)))->toHaveCount(1);
});

test('it reports zero when there is nothing to consume', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->once();
    $channel->shouldReceive('basic_get')->once()->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('subscription-created:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});
