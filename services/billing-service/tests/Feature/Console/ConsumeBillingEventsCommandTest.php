<?php

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

function aQueuedAmqpMessage(string $body, string $routingKey, array $headers = []): AMQPMessage
{
    $message = new AMQPMessage($body, [
        'application_headers' => new AMQPTable(array_merge([
            'event_id' => (string) Str::uuid(),
            'occurred_at' => (new DateTimeImmutable)->format(DATE_ATOM),
        ], $headers)),
    ]);
    $deliveryChannel = Mockery::mock(AMQPChannel::class);
    $deliveryChannel->shouldReceive('basic_ack')->once()->with(1, false);
    $message->setChannel($deliveryChannel);
    $message->setDeliveryInfo(1, false, 'billing.events', $routingKey);

    return $message;
}

test('it declares the queue with bindings for every event type it consumes', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once()->with('billing.events.v1', false, true, false, false);
    $channel->shouldReceive('queue_bind')->once()->with('billing.events.v1', 'billing.events', 'subscription.created.v1');
    $channel->shouldReceive('queue_bind')->once()->with('billing.events.v1', 'billing.events', 'payment.succeeded.v1');
    $channel->shouldReceive('basic_get')->once()->with('billing.events.v1')->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('billing-events:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});

test('it routes a subscription.created.v1 message to the invoice-creation flow', function () {
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
    $message = aQueuedAmqpMessage($body, 'subscription.created.v1');
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('billing-events:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    expect(app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($merchantId)))->toHaveCount(1);
});

test('it routes a payment.succeeded.v1 message to the mark-invoice-paid flow', function () {
    $merchantId = MerchantId::generate();
    $invoice = Invoice::reconstitute(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
        Money::of(1999, Currency::USD),
        Money::of(1999, Currency::USD),
        InvoiceStatus::Open,
        null,
        null,
        null,
    );
    (new EloquentInvoiceRepository(new InvoiceMapper))->save($invoice);
    $body = json_encode([
        'payment_id' => (string) Str::uuid(),
        'invoice_id' => $invoice->id()->toString(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
    ]);
    $message = aQueuedAmqpMessage($body, 'payment.succeeded.v1');
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('billing-events:consume')
        ->expectsOutputToContain('Consumed 1 message(s).')
        ->assertExitCode(0);

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $merchantId);
    expect($persisted->status())->toBe(InvoiceStatus::Paid);
});

test('it reports zero when there is nothing to consume', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('queue_declare')->once();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_get')->once()->andReturn(null);
    $this->app->instance(AMQPChannel::class, $channel);

    $this->artisan('billing-events:consume')
        ->expectsOutputToContain('Consumed 0 message(s).')
        ->assertExitCode(0);
});
