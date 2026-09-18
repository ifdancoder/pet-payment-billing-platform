<?php

namespace App\Domain\User\Exceptions;

use InvalidArgumentException;

final class InvalidUserId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid user id.");
    }
}
