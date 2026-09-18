<?php

namespace App\Application\User\Commands\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
