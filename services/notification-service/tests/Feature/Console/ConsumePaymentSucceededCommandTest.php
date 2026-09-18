<?php

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

test('it consumes and acks one pending message and reports it', function () {
    $merchantId = MerchantId::generate()->toString();
    $customerId = (string) Str::uuid();
    Http::fake([
        "*/api/v1/customers/{$customerId}" => Http::response(['id' => $customerId, 'email' => 'jane@example.com', 'name' => 'Jane Doe'], 200),
    ]);
    $body = json_encode([
        'payment_id' => (string) Str::uuid(),
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
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
    $message->setDeliveryInfo(1, false, 'billing.events', 'payment.succeeded.v1');

    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once()->with('notification.payment-succeeded', false, true, false, false);
    $channel->shouldReceive('queue_bind')->once()->with('notification.payment-succeeded', 'billing.events', 'payment.succeeded.v1');
    $channel->shouldReceive('basic_get')->once()->with('notification.payment-succeeded')->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('payment-succeeded:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    expect(app(INotificationRepositoryPort::class)->all(MerchantId::fromString($merchantId)))->toHaveCount(1);
});

test('it reports zero when there is nothing to consume', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->once();
    $channel->shouldReceive('basic_get')->once()->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('payment-succeeded:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});
