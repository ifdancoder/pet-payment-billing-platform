<?php

namespace App\Application\User\Commands\DisableUser;

use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\UserId;

final class DisableUserHandler
{
    public function __construct(private readonly IUserRepositoryPort $repository) {}

    public function handle(DisableUserCommand $command): User
    {
        $user = $this->repository->get(UserId::fromString($command->userId));

        $user->disable();

        $this->repository->save($user);

        return $user;
    }
}
