<?php

use App\Domain\User\Exceptions\InvalidUserId;
use App\Domain\User\ValueObjects\UserId;

test('it generates a valid unique id', function () {
    $a = UserId::generate();
    $b = UserId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = UserId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    UserId::fromString('not-a-uuid');
})->throws(InvalidUserId::class, '"not-a-uuid" is not a valid user id.');

test('two ids with the same value are equal', function () {
    $id = UserId::generate();

    expect($id->equals(UserId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = UserId::generate();

    expect((string) $id)->toBe($id->toString());
});
