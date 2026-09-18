<?php

use App\Application\User\Commands\DisableUser\DisableUserCommand;
use App\Application\User\Commands\DisableUser\DisableUserHandler;
use App\Application\User\Commands\RegisterUser\RegisterUserCommand;
use App\Application\User\Commands\RegisterUser\RegisterUserHandler;
use App\Domain\User\Exceptions\UserNotFound;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserStatus;

test('handle disables the user', function () {
    $user = app(RegisterUserHandler::class)->handle(new RegisterUserCommand('alice@example.com', 'correct-horse-battery-staple'));

    $disabled = app(DisableUserHandler::class)->handle(new DisableUserCommand($user->id()->toString()));

    expect($disabled->status())->toBe(UserStatus::Disabled);
});

test('handle throws UserNotFound when no user matches', function () {
    app(DisableUserHandler::class)->handle(new DisableUserCommand(UserId::generate()->toString()));
})->throws(UserNotFound::class);
