<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('The supplied credentials are invalid.');
    }
}
