<?php

namespace App\Infrastructure\User\Adapters\Persistence\Mappers;

use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserStatus;
use App\Infrastructure\User\Adapters\Persistence\Models\UserModel;

final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            UserId::fromString($model->id),
            Email::fromString($model->email),
            $model->password_hash,
            UserStatus::from($model->status),
        );
    }

    public function toModel(User $user, ?UserModel $model = null): UserModel
    {
        $model ??= new UserModel;

        $model->id = $user->id()->toString();
        $model->email = $user->email()->toString();
        $model->password_hash = $user->passwordHash();
        $model->status = $user->status()->value;

        return $model;
    }
}
