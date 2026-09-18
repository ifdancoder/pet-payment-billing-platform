<?php

namespace App\Domain\Membership\Exceptions;

use InvalidArgumentException;

final class InvalidMembershipId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid membership id.");
    }
}
