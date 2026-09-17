<?php

use App\Application\Invoice\Commands\VoidInvoice\VoidInvoiceCommand;
use App\Application\Invoice\Commands\VoidInvoice\VoidInvoiceHandler;
use App\Domain\Invoice\Exceptions\InvalidInvoiceTransition;
use App\Domain\Invoice\Exceptions\InvoiceAlreadyVoided;
use App\Domain\Invoice\Exceptions\InvoiceNotFound;
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

function seedInvoiceWithStatus(MerchantId $merchantId, InvoiceStatus $status): Invoice
{
    $repository = new EloquentInvoiceRepository(new InvoiceMapper);
    $invoice = Invoice::reconstitute(
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        SubscriptionId::generate(),
        BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00')),
        [InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Subscription', Money::of(1999, Currency::USD), 1)],
        Money::of(1999, Currency::USD),
        Money::of(1999, Currency::USD),
        $status,
        $status === InvoiceStatus::Paid ? PaymentId::generate() : null,
        $status === InvoiceStatus::Paid ? new DateTimeImmutable : null,
        null,
    );
    $repository->save($invoice);

    return $invoice;
}

test('handle voids an Open invoice', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedInvoiceWithStatus($merchantId, InvoiceStatus::Open);
    $handler = app(VoidInvoiceHandler::class);

    $voided = $handler->handle(new VoidInvoiceCommand($merchantId->toString(), $invoice->id()->toString()));

    expect($voided->status())->toBe(InvoiceStatus::Void)
        ->and($voided->voidedAt())->not->toBeNull();
});

test('handle throws InvoiceNotFound when no invoice matches', function () {
    app(VoidInvoiceHandler::class)->handle(new VoidInvoiceCommand(
        MerchantId::generate()->toString(),
        InvoiceId::generate()->toString(),
    ));
})->throws(InvoiceNotFound::class);

test('handle throws InvoiceAlreadyVoided when the invoice is already Void', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedInvoiceWithStatus($merchantId, InvoiceStatus::Void);

    app(VoidInvoiceHandler::class)->handle(new VoidInvoiceCommand($merchantId->toString(), $invoice->id()->toString()));
})->throws(InvoiceAlreadyVoided::class);

test('handle throws InvalidInvoiceTransition when the invoice is already Paid', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedInvoiceWithStatus($merchantId, InvoiceStatus::Paid);

    app(VoidInvoiceHandler::class)->handle(new VoidInvoiceCommand($merchantId->toString(), $invoice->id()->toString()));
})->throws(InvalidInvoiceTransition::class);

test('handle records an InvoiceVoided integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $invoice = seedInvoiceWithStatus($merchantId, InvoiceStatus::Open);

    app(VoidInvoiceHandler::class)->handle(new VoidInvoiceCommand($merchantId->toString(), $invoice->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $voided = collect($unpublished)->firstWhere('eventType', 'invoice.voided.v1');
    expect($voided)->not->toBeNull()
        ->and($voided->aggregateId)->toBe($invoice->id()->toString());
});
