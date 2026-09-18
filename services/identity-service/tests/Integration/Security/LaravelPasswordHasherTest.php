<?php

use App\Infrastructure\User\Adapters\Security\LaravelPasswordHasher;

test('hash produces a value that verify accepts for the original password', function () {
    $hasher = app(LaravelPasswordHasher::class);

    $hash = $hasher->hash('correct-horse-battery-staple');

    expect($hasher->verify('correct-horse-battery-staple', $hash))->toBeTrue();
});

test('hash does not store the plain password', function () {
    $hasher = app(LaravelPasswordHasher::class);

    $hash = $hasher->hash('correct-horse-battery-staple');

    expect($hash)->not->toBe('correct-horse-battery-staple');
});

test('verify rejects the wrong password', function () {
    $hasher = app(LaravelPasswordHasher::class);
    $hash = $hasher->hash('correct-horse-battery-staple');

    expect($hasher->verify('wrong-password', $hash))->toBeFalse();
});
