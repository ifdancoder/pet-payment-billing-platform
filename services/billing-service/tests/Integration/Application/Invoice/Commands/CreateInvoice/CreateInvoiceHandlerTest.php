<?php

use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceCommand;
use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceHandler;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\ValueObjects\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function aCreateInvoiceCommand(array $overrides = []): CreateInvoiceCommand
{
    $defaults = [
        'eventId' => (string) Str::uuid(),
        'eventType' => 'subscription.created.v1',
        'merchantId' => MerchantId::generate()->toString(),
        'customerId' => (string) Str::uuid(),
        'subscriptionId' => SubscriptionId::generate()->toString(),
        'productId' => (string) Str::uuid(),
        'priceId' => (string) Str::uuid(),
        'description' => 'Subscription',
        'amountMinorUnits' => 1999,
        'currency' => 'USD',
        'billingInterval' => 'month',
        'billingIntervalCount' => 1,
        'periodStart' => new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
    ];
    $args = array_merge($defaults, $overrides);

    return new CreateInvoiceCommand(...$args);
}

test('handle creates an Open invoice with one line for the subscription amount', function () {
    $command = aCreateInvoiceCommand();

    $invoice = app(CreateInvoiceHandler::class)->handle($command);

    expect($invoice)->not->toBeNull()
        ->and($invoice->merchantId()->toString())->toBe($command->merchantId)
        ->and($invoice->subscriptionId()->toString())->toBe($command->subscriptionId)
        ->and($invoice->status())->toBe(InvoiceStatus::Open)
        ->and($invoice->total()->amountMinorUnits())->toBe(1999)
        ->and($invoice->lines())->toHaveCount(1)
        ->and($invoice->period()->start())->toEqual($command->periodStart);
});

test('handle computes the period end from the billing interval and count', function () {
    $command = aCreateInvoiceCommand(['billingInterval' => 'month', 'billingIntervalCount' => 3]);

    $invoice = app(CreateInvoiceHandler::class)->handle($command);

    expect($invoice->period()->end())->toEqual($command->periodStart->modify('+3 month'));
});

test('handle persists the invoice', function () {
    $command = aCreateInvoiceCommand();

    $invoice = app(CreateInvoiceHandler::class)->handle($command);

    $persisted = app(IInvoiceRepositoryPort::class)->get($invoice->id(), $invoice->merchantId());
    expect($persisted->id()->equals($invoice->id()))->toBeTrue();
});

test('handle records an InvoiceCreated integration event in the outbox', function () {
    $command = aCreateInvoiceCommand();

    $invoice = app(CreateInvoiceHandler::class)->handle($command);

    $unpublished = app(IOutboxPort::class)->unpublished();
    $created = collect($unpublished)->firstWhere('eventType', 'invoice.created.v1');
    expect($created)->not->toBeNull()
        ->and($created->aggregateId)->toBe($invoice->id()->toString())
        ->and($created->payload['amount_minor_units'])->toBe(1999)
        ->and($created->payload['subscription_id'])->toBe($command->subscriptionId);
});

test('handle is a no-op and returns null when the same event id is redelivered', function () {
    $command = aCreateInvoiceCommand();
    app(CreateInvoiceHandler::class)->handle($command);

    $result = app(CreateInvoiceHandler::class)->handle($command);

    expect($result)->toBeNull()
        ->and(app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($command->merchantId)))->toHaveCount(1);
});

test('handle returns the existing invoice instead of double-billing when a different event targets the same billing cycle', function () {
    $subscriptionId = SubscriptionId::generate()->toString();
    $periodStart = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $first = aCreateInvoiceCommand(['subscriptionId' => $subscriptionId, 'periodStart' => $periodStart]);
    $original = app(CreateInvoiceHandler::class)->handle($first);

    $second = aCreateInvoiceCommand([
        'eventId' => (string) Str::uuid(),
        'subscriptionId' => $subscriptionId,
        'periodStart' => $periodStart,
    ]);
    $result = app(CreateInvoiceHandler::class)->handle($second);

    expect($result->id()->equals($original->id()))->toBeTrue()
        ->and(app(IInvoiceRepositoryPort::class)->all(MerchantId::fromString($first->merchantId)))->toHaveCount(1);
});
