<?php

namespace App\Infrastructure\User\Adapters\Persistence\Repositories;

use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Domain\User\Exceptions\UserNotFound;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\User\Adapters\Persistence\Mappers\UserMapper;
use App\Infrastructure\User\Adapters\Persistence\Models\UserModel;

final class EloquentUserRepository implements IUserRepositoryPort
{
    public function __construct(private readonly UserMapper $mapper) {}

    public function save(User $user): void
    {
        $model = UserModel::query()->find($user->id()->toString());

        $this->mapper->toModel($user, $model)->save();
    }

    public function get(UserId $id): User
    {
        $model = UserModel::query()->find($id->toString());

        if ($model === null) {
            throw UserNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()->where('email', $email->toString())->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
