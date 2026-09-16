<?php

use App\Shared\Domain\Exceptions\InvalidMerchantId;
use App\Shared\Domain\ValueObjects\MerchantId;

test('it generates a valid unique id', function () {
    $a = MerchantId::generate();
    $b = MerchantId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = MerchantId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    MerchantId::fromString('not-a-uuid');
})->throws(InvalidMerchantId::class, '"not-a-uuid" is not a valid merchant id.');

test('two ids with the same value are equal', function () {
    $id = MerchantId::generate();

    expect($id->equals(MerchantId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = MerchantId::generate();

    expect((string) $id)->toBe($id->toString());
});
