<?php

use App\Domain\Product\Exceptions\InvalidProductId;
use App\Domain\Product\ValueObjects\ProductId;

test('generate creates a value that round-trips through fromString', function () {
    $id = ProductId::generate();

    expect(ProductId::fromString($id->toString())->equals($id))->toBeTrue();
});

test('two ids with the same value are equal', function () {
    $value = '9f8e7d6c-5b4a-4321-9876-abcdef012345';

    expect(ProductId::fromString($value)->equals(ProductId::fromString($value)))->toBeTrue();
});

test('two ids with different values are not equal', function () {
    expect(ProductId::generate()->equals(ProductId::generate()))->toBeFalse();
});

test('fromString rejects a non-uuid value', function () {
    ProductId::fromString('not-a-uuid');
})->throws(InvalidProductId::class);

test('toString returns the underlying value', function () {
    $value = '9f8e7d6c-5b4a-4321-9876-abcdef012345';

    expect(ProductId::fromString($value)->toString())->toBe($value);
});
