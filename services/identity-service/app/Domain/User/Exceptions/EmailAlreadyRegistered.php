<?php

namespace App\Domain\User\Exceptions;

use App\Domain\User\ValueObjects\Email;
use RuntimeException;

final class EmailAlreadyRegistered extends RuntimeException
{
    public static function forEmail(Email $email): self
    {
        return new self("\"{$email->toString()}\" is already registered.");
    }
}
