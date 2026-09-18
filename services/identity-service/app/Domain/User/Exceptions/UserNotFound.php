<?php

namespace App\Domain\User\Exceptions;

use App\Domain\User\ValueObjects\UserId;
use RuntimeException;

final class UserNotFound extends RuntimeException
{
    public static function withId(UserId $id): self
    {
        return new self("User \"{$id->toString()}\" was not found.");
    }
}
