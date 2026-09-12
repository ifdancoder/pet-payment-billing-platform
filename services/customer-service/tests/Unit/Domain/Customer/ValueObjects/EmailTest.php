<?php

use App\Domain\Customer\Exceptions\InvalidEmail;
use App\Domain\Customer\ValueObjects\Email;

test('fromString accepts a valid email and preserves it', function () {
    expect(Email::fromString('jane@example.com')->toString())->toBe('jane@example.com');
});

test('fromString rejects a value without an @', function () {
    Email::fromString('not-an-email');
})->throws(InvalidEmail::class);

test('fromString rejects an empty value', function () {
    Email::fromString('');
})->throws(InvalidEmail::class);

test('two emails with the same value are equal', function () {
    expect(Email::fromString('jane@example.com')->equals(Email::fromString('jane@example.com')))->toBeTrue();
});

test('two emails with different values are not equal', function () {
    expect(Email::fromString('jane@example.com')->equals(Email::fromString('john@example.com')))->toBeFalse();
});
