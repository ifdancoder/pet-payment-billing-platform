<?php

namespace App\Domain\Notification\Exceptions;

use InvalidArgumentException;

final class InvalidNotificationId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid notification id.");
    }
}
