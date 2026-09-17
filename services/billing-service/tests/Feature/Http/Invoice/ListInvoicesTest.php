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

function seedAnInvoice(MerchantId $merchantId): Invoice
{
    $invoice = Invoice::create(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
    );
    (new EloquentInvoiceRepository(new InvoiceMapper))->save($invoice);

    return $invoice;
}

test('a request returns every existing invoice for the given merchant', function () {
    $merchantId = MerchantId::generate();
    seedAnInvoice($merchantId);
    seedAnInvoice($merchantId);
    seedAnInvoice(MerchantId::generate());

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/invoices");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no invoices for the given merchant', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/invoices');

    $response->assertOk()->assertJsonCount(0, 'data');
});
