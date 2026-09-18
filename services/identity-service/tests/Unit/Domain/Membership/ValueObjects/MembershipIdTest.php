<?php

use App\Domain\Membership\Exceptions\InvalidMembershipId;
use App\Domain\Membership\ValueObjects\MembershipId;

test('it generates a valid unique id', function () {
    $a = MembershipId::generate();
    $b = MembershipId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = MembershipId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    MembershipId::fromString('not-a-uuid');
})->throws(InvalidMembershipId::class, '"not-a-uuid" is not a valid membership id.');

test('two ids with the same value are equal', function () {
    $id = MembershipId::generate();

    expect($id->equals(MembershipId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = MembershipId::generate();

    expect((string) $id)->toBe($id->toString());
});
