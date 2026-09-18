<?php

namespace App\Application\User\Commands\DisableUser;

final class DisableUserCommand
{
    public function __construct(public readonly string $userId) {}
}
