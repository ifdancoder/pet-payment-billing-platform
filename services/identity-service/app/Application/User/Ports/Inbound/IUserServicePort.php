<?php

namespace App\Application\User\Ports\Inbound;

use App\Application\User\Commands\DisableUser\DisableUserCommand;
use App\Application\User\Commands\RegisterUser\RegisterUserCommand;
use App\Domain\User\User;

interface IUserServicePort
{
    public function registerUser(RegisterUserCommand $command): User;

    public function disableUser(DisableUserCommand $command): User;
}
