<?php

use App\Domain\Payment\Exceptions\InvalidCustomerId;
use App\Domain\Payment\ValueObjects\CustomerId;

test('it generates a valid unique id', function () {
    $a = CustomerId::generate();
    $b = CustomerId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = CustomerId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    CustomerId::fromString('not-a-uuid');
})->throws(InvalidCustomerId::class, '"not-a-uuid" is not a valid customer id.');

test('two ids with the same value are equal', function () {
    $id = CustomerId::generate();

    expect($id->equals(CustomerId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = CustomerId::generate();

    expect((string) $id)->toBe($id->toString());
});
