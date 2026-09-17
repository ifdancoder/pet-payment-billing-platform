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
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a valid request voids an Open invoice', function () {
    $merchantId = MerchantId::generate();
    $invoice = Invoice::create(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
    );
    (new EloquentInvoiceRepository(new InvoiceMapper))->save($invoice);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/invoices/{$invoice->id()->toString()}/void");

    $response->assertOk()->assertJsonPath('data.status', 'void');
});

test('a request for a non-existent invoice returns not found', function () {
    $response = $this->postJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/invoices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab/void');

    $response->assertNotFound();
});

test('a request to void an already-Paid invoice returns a conflict', function () {
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
        InvoiceStatus::Paid,
        PaymentId::generate(),
        new DateTimeImmutable,
        null,
    );
    (new EloquentInvoiceRepository(new InvoiceMapper))->save($invoice);

    $response = $this->postJson("/api/v1/merchants/{$merchantId->toString()}/invoices/{$invoice->id()->toString()}/void");

    $response->assertConflict();
});
