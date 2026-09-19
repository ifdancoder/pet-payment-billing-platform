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
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function seedOpenInvoiceForPaymentSucceededConsumer(MerchantId $merchantId): Invoice
{
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

    return $invoice;
}

function aPaymentSucceededPayload(InvoiceId $invoiceId, MerchantId $merchantId, array $overrides = []): array
{
    return array_merge([
        'payment_id' => (string) Str::uuid(),
        'invoice_id' => $invoiceId->toString(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
    ], $overrides);
}

test('handle marks the invoice Paid', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForPaymentSucceededConsumer($merchantId);
    $payload = aPaymentSucceededPayload($invoice->id(), $merchantId);

    app(PaymentSucceededConsumer::class)->handle((string) Str::uuid(), $payload);

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $merchantId);
    expect($persisted->status())->toBe(InvoiceStatus::Paid)
        ->and($persisted->paymentId()->toString())->toBe($payload['payment_id']);
});

test('handle does nothing when the same event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForPaymentSucceededConsumer($merchantId);
    $payload = aPaymentSucceededPayload($invoice->id(), $merchantId);
    $eventId = (string) Str::uuid();

    app(PaymentSucceededConsumer::class)->handle($eventId, $payload);
    app(PaymentSucceededConsumer::class)->handle($eventId, $payload);

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $merchantId);
    expect($persisted->status())->toBe(InvoiceStatus::Paid);
});
