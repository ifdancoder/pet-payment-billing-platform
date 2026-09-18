<?php

namespace App\Domain\Notification\Exceptions;

use InvalidArgumentException;

final class InvalidProviderReference extends InvalidArgumentException
{
    public static function mustNotBeEmpty(): self
    {
        return new self('A provider reference must not be empty.');
    }
}
