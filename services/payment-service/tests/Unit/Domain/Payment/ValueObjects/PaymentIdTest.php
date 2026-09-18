<?php

use App\Domain\Payment\Exceptions\InvalidPaymentId;
use App\Domain\Payment\ValueObjects\PaymentId;

test('it generates a valid unique id', function () {
    $a = PaymentId::generate();
    $b = PaymentId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = PaymentId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    PaymentId::fromString('not-a-uuid');
})->throws(InvalidPaymentId::class, '"not-a-uuid" is not a valid payment id.');

test('two ids with the same value are equal', function () {
    $id = PaymentId::generate();

    expect($id->equals(PaymentId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = PaymentId::generate();

    expect((string) $id)->toBe($id->toString());
});
