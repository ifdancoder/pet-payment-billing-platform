<?php

use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Infrastructure\Invoice\Adapters\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request returns the matching invoice with its lines', function () {
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

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/invoices/{$invoice->id()->toString()}");

    $response->assertOk()
        ->assertJsonPath('data.id', $invoice->id()->toString())
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.total_amount_minor_units', 1999)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonCount(1, 'data.lines')
        ->assertJsonPath('data.lines.0.description', 'Subscription');
});

test('a request for a non-existent invoice returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/invoices/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for an invoice belonging to a different merchant returns not found', function () {
    $invoice = Invoice::create(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
    );
    (new EloquentInvoiceRepository(new InvoiceMapper))->save($invoice);

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/invoices/{$invoice->id()->toString()}");

    $response->assertNotFound();
});
