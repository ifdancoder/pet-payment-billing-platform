<?php

use App\Application\Invoice\Queries\ListInvoices\ListInvoicesHandler;
use App\Application\Invoice\Queries\ListInvoices\ListInvoicesQuery;
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

function anInvoiceFor(MerchantId $merchantId): Invoice
{
    return Invoice::create(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
    );
}

test('handle returns every persisted invoice for the given merchant', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $merchantId = MerchantId::generate();
    $repository->save(anInvoiceFor($merchantId));
    $repository->save(anInvoiceFor($merchantId));
    $repository->save(anInvoiceFor(MerchantId::generate()));
    $handler = new ListInvoicesHandler($repository);

    $invoices = $handler->handle(new ListInvoicesQuery($merchantId->toString()));

    expect($invoices)->toHaveCount(2);
});

test('handle returns an empty array when there are no invoices for the given merchant', function () {
    $handler = new ListInvoicesHandler(new EloquentInvoiceRepository(new InvoiceMapper));

    expect($handler->handle(new ListInvoicesQuery(MerchantId::generate()->toString())))->toBe([]);
});
