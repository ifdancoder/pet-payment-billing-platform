<?php

namespace App\Domain\ApiKey\Exceptions;

use RuntimeException;

final class InvalidApiKey extends RuntimeException
{
    public function __construct() { parent::__construct('The API key is invalid or revoked.'); }
}
