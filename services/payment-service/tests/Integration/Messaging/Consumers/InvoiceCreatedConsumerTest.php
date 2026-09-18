<?php

use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Infrastructure\Payment\Adapters\Messaging\Consumers\InvoiceCreatedConsumer;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function anInvoiceCreatedPayload(array $overrides = []): array
{
    return array_merge([
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => MerchantId::generate()->toString(),
        'customer_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
    ], $overrides);
}

test('handle creates a payment and processes it to Succeeded', function () {
    $payload = anInvoiceCreatedPayload();

    app(InvoiceCreatedConsumer::class)->handle((string) Str::uuid(), $payload);

    $payments = app(IPaymentRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($payments)->toHaveCount(1)
        ->and($payments[0]->invoiceId()->toString())->toBe($payload['invoice_id'])
        ->and($payments[0]->status())->toBe(PaymentStatus::Succeeded)
        ->and($payments[0]->attempts())->toHaveCount(1);
});

test('handle does nothing when the same event id is redelivered', function () {
    $payload = anInvoiceCreatedPayload();
    $eventId = (string) Str::uuid();
    app(InvoiceCreatedConsumer::class)->handle($eventId, $payload);

    app(InvoiceCreatedConsumer::class)->handle($eventId, $payload);

    $payments = app(IPaymentRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($payments)->toHaveCount(1)
        ->and($payments[0]->attempts())->toHaveCount(1);
});
