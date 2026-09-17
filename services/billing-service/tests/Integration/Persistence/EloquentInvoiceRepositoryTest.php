<?php

use App\Domain\Invoice\Exceptions\InvoiceNotFound;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PriceId;
use App\Domain\Invoice\ValueObjects\ProductId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Models\InvoiceModel;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function makeInvoice(?MerchantId $merchantId = null, ?SubscriptionId $subscriptionId = null): Invoice
{
    return Invoice::create(
        InvoiceId::generate(),
        $merchantId ?? MerchantId::generate(),
        CustomerId::generate(),
        $subscriptionId ?? SubscriptionId::generate(),
        BillingPeriod::of(
            new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        ),
        [
            InvoiceLine::create(InvoiceLineId::generate(), ProductId::generate(), PriceId::generate(), 'Pro Plan', Money::of(2000, Currency::USD), 1),
            InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Extra seat', Money::of(500, Currency::USD), 3),
        ],
    );
}

test('save persists a new invoice with its lines', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $invoice = makeInvoice();

    $repository->save($invoice);

    $model = InvoiceModel::query()->with('lines')->find($invoice->id()->toString());
    expect($model)->not->toBeNull()
        ->and($model->lines)->toHaveCount(2)
        ->and($model->subtotal_amount_minor_units)->toBe(3500);
});

test('save does not duplicate lines when saving an already-persisted invoice again', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $invoice = makeInvoice();
    $repository->save($invoice);

    $invoice->void(new DateTimeImmutable);
    $repository->save($invoice);

    $model = InvoiceModel::query()->with('lines')->find($invoice->id()->toString());
    expect($model->lines)->toHaveCount(2)
        ->and($model->status)->toBe(3);
});

test('get returns the matching invoice for the owning merchant, with its lines and totals intact', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $merchantId = MerchantId::generate();
    $invoice = makeInvoice($merchantId);
    $repository->save($invoice);

    $found = $repository->get($invoice->id(), $merchantId);

    expect($found->id()->equals($invoice->id()))->toBeTrue()
        ->and($found->lines())->toHaveCount(2)
        ->and($found->subtotal()->amountMinorUnits())->toBe(3500)
        ->and($found->total()->amountMinorUnits())->toBe(3500)
        ->and($found->period()->equals($invoice->period()))->toBeTrue();

    $lineWithProduct = collect($found->lines())->first(fn (InvoiceLine $l) => $l->productId() !== null);
    expect($lineWithProduct)->not->toBeNull()
        ->and($lineWithProduct->description())->toBe('Pro Plan');
});

test('get throws InvoiceNotFound when no invoice matches', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);

    $repository->get(InvoiceId::generate(), MerchantId::generate());
})->throws(InvoiceNotFound::class);

test('get throws InvoiceNotFound when the invoice belongs to a different merchant', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $invoice = makeInvoice();
    $repository->save($invoice);

    $repository->get($invoice->id(), MerchantId::generate());
})->throws(InvoiceNotFound::class);

test('all returns every persisted invoice for the given merchant', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $merchantId = MerchantId::generate();
    $repository->save(makeInvoice($merchantId));
    $repository->save(makeInvoice($merchantId));
    $repository->save(makeInvoice());

    expect($repository->all($merchantId))->toHaveCount(2);
});

test('all returns an empty array when there are no invoices for the given merchant', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);

    expect($repository->all(MerchantId::generate()))->toBe([]);
});

test('findByBillingCycle returns the matching invoice for the subscription and period start', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $subscriptionId = SubscriptionId::generate();
    $invoice = makeInvoice(null, $subscriptionId);
    $repository->save($invoice);

    $found = $repository->findByBillingCycle($subscriptionId, $invoice->period()->start());

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($invoice->id()))->toBeTrue();
});

test('findByBillingCycle returns null when no invoice matches', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);

    $found = $repository->findByBillingCycle(SubscriptionId::generate(), new DateTimeImmutable);

    expect($found)->toBeNull();
});
