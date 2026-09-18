<?php

use App\Domain\Notification\Exceptions\InvalidNotificationId;
use App\Domain\Notification\ValueObjects\NotificationId;

test('it generates a valid unique id', function () {
    $a = NotificationId::generate();
    $b = NotificationId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = NotificationId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    NotificationId::fromString('not-a-uuid');
})->throws(InvalidNotificationId::class, '"not-a-uuid" is not a valid notification id.');

test('two ids with the same value are equal', function () {
    $id = NotificationId::generate();

    expect($id->equals(NotificationId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = NotificationId::generate();

    expect((string) $id)->toBe($id->toString());
});
