<?php

use App\Domain\Notification\Exceptions\InvalidDeliveryAttemptId;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;

test('it generates a valid unique id', function () {
    $a = DeliveryAttemptId::generate();
    $b = DeliveryAttemptId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = DeliveryAttemptId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    DeliveryAttemptId::fromString('not-a-uuid');
})->throws(InvalidDeliveryAttemptId::class, '"not-a-uuid" is not a valid delivery attempt id.');

test('two ids with the same value are equal', function () {
    $id = DeliveryAttemptId::generate();

    expect($id->equals(DeliveryAttemptId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = DeliveryAttemptId::generate();

    expect((string) $id)->toBe($id->toString());
});
