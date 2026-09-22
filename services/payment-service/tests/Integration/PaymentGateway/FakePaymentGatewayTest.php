<?php

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeStatus;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Infrastructure\Payment\Adapters\PaymentGateway\Fake\FakePaymentGateway;

test('charge always succeeds with a deterministic provider reference', function () {
    $attemptId = PaymentAttemptId::generate();
    $gateway = new FakePaymentGateway;

    $result = $gateway->charge(new ChargeRequest($attemptId, Money::of(1999, Currency::USD)));

    expect($result->status)->toBe(ChargeStatus::Succeeded)
        ->and($result->providerReference)->toBe("fake:{$attemptId->toString()}")
        ->and($result->failureCode)->toBeNull();
});

test('charge returns the same provider reference for a retried attempt id', function () {
    $attemptId = PaymentAttemptId::generate();
    $gateway = new FakePaymentGateway;
    $request = new ChargeRequest($attemptId, Money::of(1999, Currency::USD));

    $first = $gateway->charge($request);
    $second = $gateway->charge($request);

    expect($first->providerReference)->toBe($second->providerReference);
});

test('charge declines a request for the reserved decline-trigger amount, regardless of currency', function () {
    $gateway = new FakePaymentGateway;

    $result = $gateway->charge(new ChargeRequest(
        PaymentAttemptId::generate(),
        Money::of(FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS, Currency::EUR),
    ));

    expect($result->status)->toBe(ChargeStatus::Failed)
        ->and($result->failureCode)->toBe('card_declined')
        ->and($result->providerReference)->toBeNull();
});

test('charge succeeds for an amount merely close to the decline-trigger amount', function () {
    $gateway = new FakePaymentGateway;

    $result = $gateway->charge(new ChargeRequest(
        PaymentAttemptId::generate(),
        Money::of(FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS - 1, Currency::USD),
    ));

    expect($result->status)->toBe(ChargeStatus::Succeeded);
});

test('the renewal trigger succeeds on subscription creation and declines the next cycle', function () {
    $gateway = new FakePaymentGateway;
    $money = Money::of(FakePaymentGateway::DECLINE_RENEWAL_TRIGGER_AMOUNT_MINOR_UNITS, Currency::USD);

    $initial = $gateway->charge(new ChargeRequest(PaymentAttemptId::generate(), $money, 'subscription_create'));
    $renewal = $gateway->charge(new ChargeRequest(PaymentAttemptId::generate(), $money, 'subscription_cycle'));

    expect($initial->status)->toBe(ChargeStatus::Succeeded)
        ->and($renewal->status)->toBe(ChargeStatus::Failed)
        ->and($renewal->failureCode)->toBe('card_declined');
});
