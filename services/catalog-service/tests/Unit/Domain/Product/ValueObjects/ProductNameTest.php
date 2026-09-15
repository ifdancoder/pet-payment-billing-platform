<?php

use App\Domain\Product\Exceptions\InvalidProductName;
use App\Domain\Product\ValueObjects\ProductName;

test('fromString accepts a normal name and preserves it', function () {
    expect(ProductName::fromString('Pro Plan')->toString())->toBe('Pro Plan');
});

test('fromString trims surrounding whitespace', function () {
    expect(ProductName::fromString('  Pro Plan  ')->toString())->toBe('Pro Plan');
});

test('fromString rejects an empty value', function () {
    ProductName::fromString('   ');
})->throws(InvalidProductName::class);

test('fromString rejects a value longer than 255 characters', function () {
    ProductName::fromString(str_repeat('a', 256));
})->throws(InvalidProductName::class);

test('two names with the same value are equal', function () {
    expect(ProductName::fromString('Pro Plan')->equals(ProductName::fromString('Pro Plan')))->toBeTrue();
});
