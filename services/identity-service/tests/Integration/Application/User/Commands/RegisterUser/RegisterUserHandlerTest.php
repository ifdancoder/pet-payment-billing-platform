<?php

use App\Application\User\Commands\RegisterUser\RegisterUserCommand;
use App\Application\User\Commands\RegisterUser\RegisterUserHandler;
use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Domain\User\Exceptions\EmailAlreadyRegistered;
use App\Domain\User\ValueObjects\UserStatus;

test('handle registers an Active user with a hashed password', function () {
    $user = app(RegisterUserHandler::class)->handle(new RegisterUserCommand('alice@example.com', 'correct-horse-battery-staple'));

    expect($user->email()->toString())->toBe('alice@example.com')
        ->and($user->status())->toBe(UserStatus::Active)
        ->and($user->passwordHash())->not->toBe('correct-horse-battery-staple');
});

test('handle persists the user', function () {
    $user = app(RegisterUserHandler::class)->handle(new RegisterUserCommand('alice@example.com', 'correct-horse-battery-staple'));

    $persisted = app(IUserRepositoryPort::class)->get($user->id());
    expect($persisted->id()->equals($user->id()))->toBeTrue();
});

test('handle throws EmailAlreadyRegistered when the email is already registered', function () {
    app(RegisterUserHandler::class)->handle(new RegisterUserCommand('alice@example.com', 'correct-horse-battery-staple'));

    app(RegisterUserHandler::class)->handle(new RegisterUserCommand('alice@example.com', 'a-different-password'));
})->throws(EmailAlreadyRegistered::class, '"alice@example.com" is already registered.');
