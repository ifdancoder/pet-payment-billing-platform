<?php

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionCreatedConsumer;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function aSubscriptionCreatedPayload(array $overrides = []): array
{
    return array_merge([
        'subscription_id' => (string) Str::uuid(),
        'merchant_id' => MerchantId::generate()->toString(),
        'customer_id' => (string) Str::uuid(),
        'price_id' => (string) Str::uuid(),
        'product_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ], $overrides);
}

test('handle creates an invoice from the payload and marks the event processed', function () {
    $payload = aSubscriptionCreatedPayload();
    $occurredAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $eventId = (string) Str::uuid();

    app(SubscriptionCreatedConsumer::class)->handle($eventId, $payload, $occurredAt);

    $invoices = app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($invoices)->toHaveCount(1)
        ->and($invoices[0]->subscriptionId()->toString())->toBe($payload['subscription_id'])
        ->and($invoices[0]->total()->amountMinorUnits())->toBe(1999)
        ->and($invoices[0]->period()->start())->toEqual($occurredAt);
});

test('handle does nothing when the same event id is redelivered', function () {
    $payload = aSubscriptionCreatedPayload();
    $eventId = (string) Str::uuid();
    app(SubscriptionCreatedConsumer::class)->handle($eventId, $payload, new DateTimeImmutable);

    app(SubscriptionCreatedConsumer::class)->handle($eventId, $payload, new DateTimeImmutable);

    $invoices = app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($invoices)->toHaveCount(1);
});
