<?php

use App\Application\Invoice\Commands\MarkInvoicePaid\MarkInvoicePaidCommand;
use App\Application\Invoice\Commands\MarkInvoicePaid\MarkInvoicePaidHandler;
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
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function seedOpenInvoiceForMarkPaid(MerchantId $merchantId): Invoice
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

test('handle marks an Open invoice Paid', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);
    $paymentId = PaymentId::generate();
    $paidAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $paid = app(MarkInvoicePaidHandler::class)->handle(new MarkInvoicePaidCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        $paymentId->toString(),
        $paidAt,
    ));

    expect($paid->status())->toBe(InvoiceStatus::Paid)
        ->and($paid->paymentId()->equals($paymentId))->toBeTrue()
        ->and($paid->paidAt())->toEqual($paidAt);
});

test('handle persists the Paid status', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);

    app(MarkInvoicePaidHandler::class)->handle(new MarkInvoicePaidCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        PaymentId::generate()->toString(),
        new DateTimeImmutable,
    ));

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $merchantId);
    expect($persisted->status())->toBe(InvoiceStatus::Paid);
});

test('handle does nothing when the same event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);
    $eventId = (string) Str::uuid();
    $command = new MarkInvoicePaidCommand($eventId, 'payment.succeeded.v1', $invoice->id()->toString(), $merchantId->toString(), PaymentId::generate()->toString(), new DateTimeImmutable);
    app(MarkInvoicePaidHandler::class)->handle($command);

    $result = app(MarkInvoicePaidHandler::class)->handle($command);

    expect($result)->toBeNull();
});

test('handle is idempotent when the invoice is already Paid via a different event id', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);
    app(MarkInvoicePaidHandler::class)->handle(new MarkInvoicePaidCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        PaymentId::generate()->toString(),
        new DateTimeImmutable,
    ));

    $result = app(MarkInvoicePaidHandler::class)->handle(new MarkInvoicePaidCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        PaymentId::generate()->toString(),
        new DateTimeImmutable,
    ));

    expect($result->status())->toBe(InvoiceStatus::Paid);
});

test('handle records an InvoicePaid integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);
    $paymentId = PaymentId::generate();

    app(MarkInvoicePaidHandler::class)->handle(new MarkInvoicePaidCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        $invoice->id()->toString(),
        $merchantId->toString(),
        $paymentId->toString(),
        new DateTimeImmutable,
    ));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $paid = collect($unpublished)->firstWhere('eventType', 'invoice.paid.v1');
    expect($paid)->not->toBeNull()
        ->and($paid->aggregateId)->toBe($invoice->id()->toString())
        ->and($paid->payload['subscription_id'])->toBe($invoice->subscriptionId()->toString())
        ->and($paid->payload['payment_id'])->toBe($paymentId->toString());
});

test('handle does not record an integration event when the event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedOpenInvoiceForMarkPaid($merchantId);
    $command = new MarkInvoicePaidCommand((string) Str::uuid(), 'payment.succeeded.v1', $invoice->id()->toString(), $merchantId->toString(), PaymentId::generate()->toString(), new DateTimeImmutable);
    app(MarkInvoicePaidHandler::class)->handle($command);

    app(MarkInvoicePaidHandler::class)->handle($command);

    expect(app(IOutboxPort::class)->unpublished())->toHaveCount(1);
});
