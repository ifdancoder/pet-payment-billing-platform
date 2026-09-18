<?php

use App\Domain\User\Exceptions\InvalidEmail;
use App\Domain\User\ValueObjects\Email;

test('it exposes the given value', function () {
    $email = Email::fromString('alice@example.com');

    expect($email->toString())->toBe('alice@example.com');
});

test('it throws when the value is not a valid email address', function () {
    Email::fromString('not-an-email');
})->throws(InvalidEmail::class, '"not-an-email" is not a valid email address.');

test('two addresses with the same value are equal', function () {
    $a = Email::fromString('alice@example.com');
    $b = Email::fromString('alice@example.com');

    expect($a->equals($b))->toBeTrue();
});

test('it can be cast to a string', function () {
    $email = Email::fromString('alice@example.com');

    expect((string) $email)->toBe('alice@example.com');
});
