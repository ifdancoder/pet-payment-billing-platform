<?php

namespace App\Application\User\Ports\Outbound;

use App\Domain\User\Exceptions\UserNotFound;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

interface IUserRepositoryPort
{
    public function save(User $user): void;

    /**
     * @throws UserNotFound
     */
    public function get(UserId $id): User;

    public function findByEmail(Email $email): ?User;
}
