<?php

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
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentFailedConsumer;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function seedOpenInvoiceForPaymentFailedConsumer(MerchantId $merchantId): Invoice
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

test('handle relays the failure as invoice.payment_failed.v1', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForPaymentFailedConsumer($merchantId);
    $payload = [
        'payment_id' => (string) Str::uuid(),
        'invoice_id' => $invoice->id()->toString(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'failure_code' => 'card_declined',
    ];

    app(PaymentFailedConsumer::class)->handle((string) Str::uuid(), $payload);

    $unpublished = app(IOutboxPort::class)->unpublished();
    $failed = collect($unpublished)->firstWhere('eventType', 'invoice.payment_failed.v1');
    expect($failed)->not->toBeNull()
        ->and($failed->payload['subscription_id'])->toBe($invoice->subscriptionId()->toString());
});
