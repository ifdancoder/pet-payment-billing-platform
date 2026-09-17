<?php

use App\Domain\Invoice\Exceptions\InvalidSubscriptionId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;

test('it generates a valid unique id', function () {
    $a = SubscriptionId::generate();
    $b = SubscriptionId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = SubscriptionId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    SubscriptionId::fromString('not-a-uuid');
})->throws(InvalidSubscriptionId::class, '"not-a-uuid" is not a valid subscription id.');

test('two ids with the same value are equal', function () {
    $id = SubscriptionId::generate();

    expect($id->equals(SubscriptionId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = SubscriptionId::generate();

    expect((string) $id)->toBe($id->toString());
});
