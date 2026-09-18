<?php

use App\Domain\Notification\Exceptions\InvalidEmailAddress;
use App\Domain\Notification\ValueObjects\EmailAddress;

test('it exposes the given value', function () {
    $email = EmailAddress::fromString('customer@example.com');

    expect($email->toString())->toBe('customer@example.com');
});

test('it throws when the value is not a valid email address', function () {
    EmailAddress::fromString('not-an-email');
})->throws(InvalidEmailAddress::class, '"not-an-email" is not a valid email address.');

test('two addresses with the same value are equal', function () {
    $a = EmailAddress::fromString('customer@example.com');
    $b = EmailAddress::fromString('customer@example.com');

    expect($a->equals($b))->toBeTrue();
});

test('two addresses with different values are not equal', function () {
    $a = EmailAddress::fromString('customer@example.com');
    $b = EmailAddress::fromString('other@example.com');

    expect($a->equals($b))->toBeFalse();
});

test('it can be cast to a string', function () {
    $email = EmailAddress::fromString('customer@example.com');

    expect((string) $email)->toBe('customer@example.com');
});
