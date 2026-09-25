<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class InvalidRefreshToken extends DomainException
{
    public function __construct()
    {
        parent::__construct('The refresh token is invalid or expired.');
    }
}
