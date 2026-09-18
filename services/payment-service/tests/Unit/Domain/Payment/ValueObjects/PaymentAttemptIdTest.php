<?php

use App\Domain\Payment\Exceptions\InvalidPaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;

test('it generates a valid unique id', function () {
    $a = PaymentAttemptId::generate();
    $b = PaymentAttemptId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = PaymentAttemptId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    PaymentAttemptId::fromString('not-a-uuid');
})->throws(InvalidPaymentAttemptId::class, '"not-a-uuid" is not a valid payment attempt id.');

test('two ids with the same value are equal', function () {
    $id = PaymentAttemptId::generate();

    expect($id->equals(PaymentAttemptId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = PaymentAttemptId::generate();

    expect((string) $id)->toBe($id->toString());
});
