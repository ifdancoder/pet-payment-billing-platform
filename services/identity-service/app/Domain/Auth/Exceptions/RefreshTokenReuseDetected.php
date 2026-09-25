<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class RefreshTokenReuseDetected extends DomainException
{
    public function __construct()
    {
        parent::__construct('Refresh token reuse was detected; the token family has been revoked.');
    }
}
