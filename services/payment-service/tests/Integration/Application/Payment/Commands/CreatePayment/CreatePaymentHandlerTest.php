<?php

use App\Application\Payment\Commands\CreatePayment\CreatePaymentCommand;
use App\Application\Payment\Commands\CreatePayment\CreatePaymentHandler;
use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function aCreatePaymentCommand(array $overrides = []): CreatePaymentCommand
{
    $defaults = [
        'eventId' => (string) Str::uuid(),
        'eventType' => 'invoice.created.v1',
        'invoiceId' => (string) Str::uuid(),
        'merchantId' => MerchantId::generate()->toString(),
        'customerId' => (string) Str::uuid(),
        'amountMinorUnits' => 1999,
        'currency' => 'USD',
    ];

    return new CreatePaymentCommand(...array_merge($defaults, $overrides));
}

test('handle creates a Pending payment for the invoice amount', function () {
    $command = aCreatePaymentCommand();

    $payment = app(CreatePaymentHandler::class)->handle($command);

    expect($payment)->not->toBeNull()
        ->and($payment->invoiceId()->toString())->toBe($command->invoiceId)
        ->and($payment->merchantId()->toString())->toBe($command->merchantId)
        ->and($payment->status())->toBe(PaymentStatus::Pending)
        ->and($payment->money()->amountMinorUnits())->toBe(1999);
});

test('handle persists the payment', function () {
    $command = aCreatePaymentCommand();

    $payment = app(CreatePaymentHandler::class)->handle($command);

    $persisted = app(IPaymentRepositoryPort::class)->get($payment->id(), $payment->merchantId());
    expect($persisted->id()->equals($payment->id()))->toBeTrue();
});

test('handle is a no-op and returns null when the same event id is redelivered', function () {
    $command = aCreatePaymentCommand();
    app(CreatePaymentHandler::class)->handle($command);

    $result = app(CreatePaymentHandler::class)->handle($command);

    expect($result)->toBeNull()
        ->and(app(IPaymentRepositoryPort::class)->all(MerchantId::fromString($command->merchantId)))->toHaveCount(1);
});

test('handle returns the existing payment instead of double-creating when a different event targets the same invoice', function () {
    $invoiceId = (string) Str::uuid();
    $first = aCreatePaymentCommand(['invoiceId' => $invoiceId]);
    $original = app(CreatePaymentHandler::class)->handle($first);

    $second = aCreatePaymentCommand(['eventId' => (string) Str::uuid(), 'invoiceId' => $invoiceId]);
    $result = app(CreatePaymentHandler::class)->handle($second);

    expect($result->id()->equals($original->id()))->toBeTrue()
        ->and(app(IPaymentRepositoryPort::class)->all(MerchantId::fromString($first->merchantId)))->toHaveCount(1);
});
