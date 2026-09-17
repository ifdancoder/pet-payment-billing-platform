<?php

use App\Domain\Invoice\Exceptions\InvalidPriceId;
use App\Domain\Invoice\ValueObjects\PriceId;

test('it generates a valid unique id', function () {
    $a = PriceId::generate();
    $b = PriceId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = PriceId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    PriceId::fromString('not-a-uuid');
})->throws(InvalidPriceId::class, '"not-a-uuid" is not a valid price id.');

test('two ids with the same value are equal', function () {
    $id = PriceId::generate();

    expect($id->equals(PriceId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = PriceId::generate();

    expect((string) $id)->toBe($id->toString());
});
