<?php

use App\Domain\Subscription\Exceptions\InvalidProductId;
use App\Domain\Subscription\ValueObjects\ProductId;

test('it generates a valid unique id', function () {
    $a = ProductId::generate();
    $b = ProductId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = ProductId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    ProductId::fromString('not-a-uuid');
})->throws(InvalidProductId::class, '"not-a-uuid" is not a valid product id.');

test('two ids with the same value are equal', function () {
    $id = ProductId::generate();

    expect($id->equals(ProductId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = ProductId::generate();

    expect((string) $id)->toBe($id->toString());
});
