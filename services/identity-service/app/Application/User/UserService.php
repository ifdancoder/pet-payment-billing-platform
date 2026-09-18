<?php

namespace App\Application\User;

use App\Application\User\Commands\DisableUser\DisableUserCommand;
use App\Application\User\Commands\DisableUser\DisableUserHandler;
use App\Application\User\Commands\RegisterUser\RegisterUserCommand;
use App\Application\User\Commands\RegisterUser\RegisterUserHandler;
use App\Application\User\Ports\Inbound\IUserServicePort;
use App\Domain\User\User;

final class UserService implements IUserServicePort
{
    public function __construct(
        private readonly RegisterUserHandler $registerUserHandler,
        private readonly DisableUserHandler $disableUserHandler,
    ) {}

    public function registerUser(RegisterUserCommand $command): User
    {
        return $this->registerUserHandler->handle($command);
    }

    public function disableUser(DisableUserCommand $command): User
    {
        return $this->disableUserHandler->handle($command);
    }
}
