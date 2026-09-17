<?php

use App\Application\Invoice\Queries\GetInvoice\GetInvoiceHandler;
use App\Application\Invoice\Queries\GetInvoice\GetInvoiceQuery;
use App\Domain\Invoice\Exceptions\InvoiceNotFound;
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

test('handle returns the matching invoice', function () {
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $merchantId = MerchantId::generate();
    $invoice = Invoice::create(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
    );
    $repository->save($invoice);
    $handler = new GetInvoiceHandler($repository);

    $found = $handler->handle(new GetInvoiceQuery($merchantId->toString(), $invoice->id()->toString()));

    expect($found->id()->equals($invoice->id()))->toBeTrue();
});

test('handle throws InvoiceNotFound when no invoice matches', function () {
    $handler = new GetInvoiceHandler(new EloquentInvoiceRepository(new InvoiceMapper));

    $handler->handle(new GetInvoiceQuery(MerchantId::generate()->toString(), InvoiceId::generate()->toString()));
})->throws(InvoiceNotFound::class);
