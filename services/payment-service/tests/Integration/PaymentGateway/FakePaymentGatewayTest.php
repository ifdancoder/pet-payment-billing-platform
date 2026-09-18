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
