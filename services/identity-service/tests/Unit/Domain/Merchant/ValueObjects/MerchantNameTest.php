<?php

use App\Domain\Merchant\Exceptions\InvalidMerchantName;
use App\Domain\Merchant\ValueObjects\MerchantName;

test('it exposes the given value', function () {
    $name = MerchantName::fromString('Acme Inc.');

    expect($name->toString())->toBe('Acme Inc.');
});

test('it trims surrounding whitespace', function () {
    $name = MerchantName::fromString('  Acme Inc.  ');

    expect($name->toString())->toBe('Acme Inc.');
});

test('it throws when the value is empty', function () {
    MerchantName::fromString('   ');
})->throws(InvalidMerchantName::class, 'Merchant name must not be empty.');

test('it throws when the value is longer than 255 characters', function () {
    MerchantName::fromString(str_repeat('a', 256));
})->throws(InvalidMerchantName::class);

test('two names with the same value are equal', function () {
    $a = MerchantName::fromString('Acme Inc.');
    $b = MerchantName::fromString('Acme Inc.');

    expect($a->equals($b))->toBeTrue();
});

test('it can be cast to a string', function () {
    $name = MerchantName::fromString('Acme Inc.');

    expect((string) $name)->toBe('Acme Inc.');
});
