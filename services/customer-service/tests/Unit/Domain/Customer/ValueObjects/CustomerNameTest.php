<?php

use App\Domain\Customer\Exceptions\InvalidCustomerName;
use App\Domain\Customer\ValueObjects\CustomerName;

test('fromString accepts a normal name and preserves it', function () {
    expect(CustomerName::fromString('Jane Doe')->toString())->toBe('Jane Doe');
});

test('fromString trims surrounding whitespace', function () {
    expect(CustomerName::fromString('  Jane Doe  ')->toString())->toBe('Jane Doe');
});

test('fromString rejects an empty value', function () {
    CustomerName::fromString('   ');
})->throws(InvalidCustomerName::class);

test('fromString rejects a value longer than 255 characters', function () {
    CustomerName::fromString(str_repeat('a', 256));
})->throws(InvalidCustomerName::class);

test('two names with the same value are equal', function () {
    expect(CustomerName::fromString('Jane Doe')->equals(CustomerName::fromString('Jane Doe')))->toBeTrue();
});
