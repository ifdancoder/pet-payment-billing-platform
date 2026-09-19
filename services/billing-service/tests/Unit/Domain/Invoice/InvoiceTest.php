<?php

use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\Events\InvoicePaid;
use App\Domain\Invoice\Events\InvoiceVoided;
use App\Domain\Invoice\Exceptions\InvalidInvoice;
use App\Domain\Invoice\Exceptions\InvalidInvoiceTransition;
use App\Domain\Invoice\Exceptions\InvoiceAlreadyVoided;
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
use App\Shared\Domain\ValueObjects\MerchantId;

function aBillingPeriod(): BillingPeriod
{
    return BillingPeriod::of(
        new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
    );
}

/**
 * @return InvoiceLine[]
 */
function someInvoiceLines(): array
{
    return [
        InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Pro Plan', Money::of(2000, Currency::USD), 1),
        InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Extra seat', Money::of(500, Currency::USD), 3),
    ];
}

test('create computes the subtotal and total from the given lines and starts out Open', function () {
    $id = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $period = aBillingPeriod();
    $lines = someInvoiceLines();

    $invoice = Invoice::create($id, $merchantId, $customerId, $subscriptionId, $period, $lines);

    expect($invoice->id()->equals($id))->toBeTrue()
        ->and($invoice->merchantId()->equals($merchantId))->toBeTrue()
        ->and($invoice->customerId()->equals($customerId))->toBeTrue()
        ->and($invoice->subscriptionId()->equals($subscriptionId))->toBeTrue()
        ->and($invoice->period()->equals($period))->toBeTrue()
        ->and($invoice->lines())->toBe($lines)
        ->and($invoice->subtotal()->amountMinorUnits())->toBe(3500)
        ->and($invoice->total()->amountMinorUnits())->toBe(3500)
        ->and($invoice->status())->toBe(InvoiceStatus::Open);
});

test('create records an InvoiceCreated event carrying the same data', function () {
    $id = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $period = aBillingPeriod();

    $invoice = Invoice::create($id, $merchantId, $customerId, $subscriptionId, $period, someInvoiceLines());
    $events = $invoice->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceCreated::class)
        ->and($events[0]->invoiceId->equals($id))->toBeTrue()
        ->and($events[0]->merchantId->equals($merchantId))->toBeTrue()
        ->and($events[0]->customerId->equals($customerId))->toBeTrue()
        ->and($events[0]->subscriptionId->equals($subscriptionId))->toBeTrue()
        ->and($events[0]->period->equals($period))->toBeTrue()
        ->and($events[0]->total->amountMinorUnits())->toBe(3500);
});

test('create throws when there are no lines', function () {
    Invoice::create(InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), SubscriptionId::generate(), aBillingPeriod(), []);
})->throws(InvalidInvoice::class, 'An invoice must have at least one line.');

test('pullRecordedEvents empties the recorded events', function () {
    $invoice = Invoice::create(InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), SubscriptionId::generate(), aBillingPeriod(), someInvoiceLines());

    $invoice->pullRecordedEvents();

    expect($invoice->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data and status without recording an event', function () {
    $id = InvoiceId::generate();
    $merchantId = MerchantId::generate();

    $invoice = Invoice::reconstitute(
        $id,
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        aBillingPeriod(),
        someInvoiceLines(),
        Money::of(3500, Currency::USD),
        Money::of(3500, Currency::USD),
        InvoiceStatus::Paid,
        PaymentId::generate(),
        new DateTimeImmutable('2026-09-05T00:00:00+00:00'),
        null,
    );

    expect($invoice->id()->equals($id))->toBeTrue()
        ->and($invoice->merchantId()->equals($merchantId))->toBeTrue()
        ->and($invoice->status())->toBe(InvoiceStatus::Paid)
        ->and($invoice->paidAt())->not->toBeNull()
        ->and($invoice->pullRecordedEvents())->toBe([]);
});

test('markPaid sets the status to Paid and records an InvoicePaid event when Open', function () {
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $invoice = Invoice::create(InvoiceId::generate(), $merchantId, $customerId, $subscriptionId, aBillingPeriod(), someInvoiceLines());
    $invoice->pullRecordedEvents();
    $paymentId = PaymentId::generate();
    $paidAt = new DateTimeImmutable('2026-09-05T00:00:00+00:00');

    $invoice->markPaid($paymentId, $paidAt);

    expect($invoice->status())->toBe(InvoiceStatus::Paid)
        ->and($invoice->paymentId()->equals($paymentId))->toBeTrue()
        ->and($invoice->paidAt())->toEqual($paidAt);
    $events = $invoice->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoicePaid::class)
        ->and($events[0]->invoiceId->equals($invoice->id()))->toBeTrue()
        ->and($events[0]->merchantId->equals($merchantId))->toBeTrue()
        ->and($events[0]->customerId->equals($customerId))->toBeTrue()
        ->and($events[0]->subscriptionId->equals($subscriptionId))->toBeTrue()
        ->and($events[0]->paymentId->equals($paymentId))->toBeTrue()
        ->and($events[0]->total->amountMinorUnits())->toBe(3500);
});

test('markPaid is idempotent when the invoice is already Paid', function () {
    $invoice = Invoice::reconstitute(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        aBillingPeriod(),
        someInvoiceLines(),
        Money::of(3500, Currency::USD),
        Money::of(3500, Currency::USD),
        InvoiceStatus::Paid,
        PaymentId::generate(),
        new DateTimeImmutable('2026-09-05T00:00:00+00:00'),
        null,
    );

    $invoice->markPaid(PaymentId::generate(), new DateTimeImmutable('2026-09-06T00:00:00+00:00'));

    expect($invoice->status())->toBe(InvoiceStatus::Paid)
        ->and($invoice->pullRecordedEvents())->toBe([]);
});

test('markPaid throws when the invoice is Void', function () {
    $invoice = Invoice::reconstitute(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        aBillingPeriod(),
        someInvoiceLines(),
        Money::of(3500, Currency::USD),
        Money::of(3500, Currency::USD),
        InvoiceStatus::Void,
        null,
        null,
        new DateTimeImmutable('2026-09-05T00:00:00+00:00'),
    );

    $invoice->markPaid(PaymentId::generate(), new DateTimeImmutable('2026-09-06T00:00:00+00:00'));
})->throws(InvalidInvoiceTransition::class);

test('void sets the status to Void and records an InvoiceVoided event when Open', function () {
    $invoice = Invoice::create(InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), SubscriptionId::generate(), aBillingPeriod(), someInvoiceLines());
    $invoice->pullRecordedEvents();
    $voidedAt = new DateTimeImmutable('2026-09-05T00:00:00+00:00');

    $invoice->void($voidedAt);

    expect($invoice->status())->toBe(InvoiceStatus::Void)
        ->and($invoice->voidedAt())->toEqual($voidedAt);
    $events = $invoice->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceVoided::class)
        ->and($events[0]->invoiceId->equals($invoice->id()))->toBeTrue();
});

test('void throws when the invoice is already Void', function () {
    $invoice = Invoice::create(InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), SubscriptionId::generate(), aBillingPeriod(), someInvoiceLines());
    $invoice->void(new DateTimeImmutable);

    $invoice->void(new DateTimeImmutable);
})->throws(InvoiceAlreadyVoided::class);

test('void throws when the invoice is already Paid', function () {
    $invoice = Invoice::reconstitute(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        aBillingPeriod(),
        someInvoiceLines(),
        Money::of(3500, Currency::USD),
        Money::of(3500, Currency::USD),
        InvoiceStatus::Paid,
        PaymentId::generate(),
        new DateTimeImmutable('2026-09-05T00:00:00+00:00'),
        null,
    );

    $invoice->void(new DateTimeImmutable);
})->throws(InvalidInvoiceTransition::class);
