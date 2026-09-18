<?php

use App\Domain\User\Events\UserDisabled;
use App\Domain\User\Events\UserRegistered;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserStatus;

test('register exposes the given data and starts out Active', function () {
    $id = UserId::generate();
    $email = Email::fromString('alice@example.com');

    $user = User::register($id, $email, 'hashed-password');

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->email()->equals($email))->toBeTrue()
        ->and($user->passwordHash())->toBe('hashed-password')
        ->and($user->status())->toBe(UserStatus::Active)
        ->and($user->isActive())->toBeTrue();
});

test('register records a UserRegistered event', function () {
    $id = UserId::generate();
    $email = Email::fromString('alice@example.com');

    $user = User::register($id, $email, 'hashed-password');

    $events = $user->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class)
        ->and($events[0]->userId->equals($id))->toBeTrue()
        ->and($events[0]->email->equals($email))->toBeTrue();
});

test('changePasswordHash replaces the stored hash', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'old-hash');

    $user->changePasswordHash('new-hash');

    expect($user->passwordHash())->toBe('new-hash');
});

test('disable sets the status to Disabled and records a UserDisabled event', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');
    $user->pullRecordedEvents();

    $user->disable();

    expect($user->status())->toBe(UserStatus::Disabled)
        ->and($user->isActive())->toBeFalse();
    $events = $user->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserDisabled::class)
        ->and($events[0]->userId->equals($user->id()))->toBeTrue();
});

test('disable is idempotent when the user is already Disabled', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');
    $user->disable();
    $user->pullRecordedEvents();

    $user->disable();

    expect($user->status())->toBe(UserStatus::Disabled)
        ->and($user->pullRecordedEvents())->toBe([]);
});

test('pullRecordedEvents empties the recorded events', function () {
    $user = User::register(UserId::generate(), Email::fromString('alice@example.com'), 'hashed-password');

    $user->pullRecordedEvents();

    expect($user->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data without recording an event', function () {
    $id = UserId::generate();

    $user = User::reconstitute($id, Email::fromString('alice@example.com'), 'hashed-password', UserStatus::Disabled);

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->status())->toBe(UserStatus::Disabled)
        ->and($user->pullRecordedEvents())->toBe([]);
});
