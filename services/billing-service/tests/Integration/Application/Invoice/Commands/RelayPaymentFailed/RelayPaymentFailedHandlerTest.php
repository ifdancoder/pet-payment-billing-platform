<?php

use App\Application\Invoice\Commands\RelayPaymentFailed\RelayPaymentFailedCommand;
use App\Application\Invoice\Commands\RelayPaymentFailed\RelayPaymentFailedHandler;
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
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function seedOpenInvoiceForRelayPaymentFailed(MerchantId $merchantId): Invoice
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

test('handle leaves the invoice Open', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForRelayPaymentFailed($merchantId);

    app(RelayPaymentFailedHandler::class)->handle(new RelayPaymentFailedCommand(
        (string) Str::uuid(),
        'payment.failed.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        (string) Str::uuid(),
        'card_declined',
    ));

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $merchantId);
    expect($persisted->status())->toBe(InvoiceStatus::Open);
});

test('handle records an InvoicePaymentFailed integration event carrying the subscription id', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForRelayPaymentFailed($merchantId);
    $paymentId = (string) Str::uuid();

    app(RelayPaymentFailedHandler::class)->handle(new RelayPaymentFailedCommand(
        (string) Str::uuid(),
        'payment.failed.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        $paymentId,
        'card_declined',
    ));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $failed = collect($unpublished)->firstWhere('eventType', 'invoice.payment_failed.v1');
    expect($failed)->not->toBeNull()
        ->and($failed->aggregateId)->toBe($invoice->id()->toString())
        ->and($failed->payload['subscription_id'])->toBe($invoice->subscriptionId()->toString())
        ->and($failed->payload['payment_id'])->toBe($paymentId)
        ->and($failed->payload['failure_code'])->toBe('card_declined');
});

test('handle does nothing when the same event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForRelayPaymentFailed($merchantId);
    $command = new RelayPaymentFailedCommand((string) Str::uuid(), 'payment.failed.v1', $invoice->id()->toString(), $merchantId->toString(), (string) Str::uuid(), 'card_declined');
    app(RelayPaymentFailedHandler::class)->handle($command);

    app(RelayPaymentFailedHandler::class)->handle($command);

    expect(app(IOutboxPort::class)->unpublished())->toHaveCount(1);
});
