<?php

use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentCommand;
use App\Application\Payment\Commands\ProcessPayment\ProcessPaymentHandler;
use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeResult;
use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;
use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

function seedPendingPayment(MerchantId $merchantId): Payment
{
    $payment = Payment::create(
        PaymentId::generate(),
        InvoiceId::generate(),
        $merchantId,
        CustomerId::generate(),
        Money::of(1999, Currency::USD),
    );
    app(IPaymentRepositoryPort::class)->save($payment);

    return $payment;
}

test('handle starts an attempt, charges via the gateway and marks the payment Succeeded', function () {
    $merchantId = MerchantId::generate();
    $payment = seedPendingPayment($merchantId);

    $result = app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    expect($result->status())->toBe(PaymentStatus::Succeeded)
        ->and($result->attempts())->toHaveCount(1)
        ->and($result->paidAt())->not->toBeNull();
});

test('handle persists the Succeeded outcome', function () {
    $merchantId = MerchantId::generate();
    $payment = seedPendingPayment($merchantId);

    app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    $persisted = app(IPaymentRepositoryPort::class)->get($payment->id(), $merchantId);
    expect($persisted->status())->toBe(PaymentStatus::Succeeded)
        ->and($persisted->attempts())->toHaveCount(1);
});

test('handle records a PaymentSucceeded integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $payment = seedPendingPayment($merchantId);

    $result = app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $succeeded = collect($unpublished)->firstWhere('eventType', 'payment.succeeded.v1');
    expect($succeeded)->not->toBeNull()
        ->and($succeeded->aggregateId)->toBe($result->id()->toString())
        ->and($succeeded->payload['amount_minor_units'])->toBe(1999);
});

test('handle marks the payment Failed and records a PaymentFailed integration event when the gateway declines', function () {
    $merchantId = MerchantId::generate();
    $payment = seedPendingPayment($merchantId);
    $gateway = Mockery::mock(IPaymentGatewayPort::class);
    $gateway->shouldReceive('charge')->once()->andReturn(ChargeResult::failed('card_declined'));
    app()->instance(IPaymentGatewayPort::class, $gateway);

    $result = app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    expect($result->status())->toBe(PaymentStatus::Failed)
        ->and($result->attempts()[0]->failureCode())->toBe('card_declined');
    $unpublished = app(IOutboxPort::class)->unpublished();
    $failed = collect($unpublished)->firstWhere('eventType', 'payment.failed.v1');
    expect($failed)->not->toBeNull()
        ->and($failed->payload['failure_code'])->toBe('card_declined');
});

test('handle allows a retry after a Failed attempt and eventually succeeds', function () {
    $merchantId = MerchantId::generate();
    $payment = seedPendingPayment($merchantId);
    $gateway = Mockery::mock(IPaymentGatewayPort::class);
    $gateway->shouldReceive('charge')->once()->andReturn(ChargeResult::failed('card_declined'));
    app()->instance(IPaymentGatewayPort::class, $gateway);
    app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    $succeedingGateway = Mockery::mock(IPaymentGatewayPort::class);
    $succeedingGateway->shouldReceive('charge')->once()->andReturnUsing(
        fn (ChargeRequest $request) => ChargeResult::succeeded("fake:{$request->attemptId->toString()}"),
    );
    app()->instance(IPaymentGatewayPort::class, $succeedingGateway);

    $result = app(ProcessPaymentHandler::class)->handle(new ProcessPaymentCommand($payment->id()->toString(), $merchantId->toString()));

    expect($result->status())->toBe(PaymentStatus::Succeeded)
        ->and($result->attempts())->toHaveCount(2);
});
