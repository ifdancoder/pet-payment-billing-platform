<?php

use App\Domain\Customer\Exceptions\InvalidCustomerId;
use App\Domain\Customer\ValueObjects\CustomerId;

test('generate creates a value that round-trips through fromString', function () {
    $id = CustomerId::generate();

    expect(CustomerId::fromString($id->toString())->equals($id))->toBeTrue();
});

test('two ids with the same value are equal', function () {
    $value = '9f8e7d6c-5b4a-4321-9876-abcdef012345';

    expect(CustomerId::fromString($value)->equals(CustomerId::fromString($value)))->toBeTrue();
});

test('two ids with different values are not equal', function () {
    expect(CustomerId::generate()->equals(CustomerId::generate()))->toBeFalse();
});

test('fromString rejects a non-uuid value', function () {
    CustomerId::fromString('not-a-uuid');
})->throws(InvalidCustomerId::class);

test('toString returns the underlying value', function () {
    $value = '9f8e7d6c-5b4a-4321-9876-abcdef012345';

    expect(CustomerId::fromString($value)->toString())->toBe($value);
});
