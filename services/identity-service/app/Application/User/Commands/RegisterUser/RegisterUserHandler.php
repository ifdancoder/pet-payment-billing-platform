<?php

namespace App\Application\User\Commands\RegisterUser;

use App\Application\User\Ports\Outbound\IPasswordHasherPort;
use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Domain\User\Exceptions\EmailAlreadyRegistered;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\UniqueConstraintViolationException;

final class RegisterUserHandler
{
    public function __construct(
        private readonly IUserRepositoryPort $repository,
        private readonly IPasswordHasherPort $hasher,
    ) {}

    /**
     * @throws EmailAlreadyRegistered
     */
    public function handle(RegisterUserCommand $command): User
    {
        $email = Email::fromString($command->email);

        if ($this->repository->findByEmail($email) !== null) {
            throw EmailAlreadyRegistered::forEmail($email);
        }

        $user = User::register(UserId::generate(), $email, $this->hasher->hash($command->password));

        try {
            $this->repository->save($user);
        } catch (UniqueConstraintViolationException) {
            // The unique constraint resolves concurrent registrations.
            throw EmailAlreadyRegistered::forEmail($email);
        }

        return $user;
    }
}
