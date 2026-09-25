<?php

namespace App\Domain\ApiKey\Exceptions;

use RuntimeException;

final class ApiKeyNotFound extends RuntimeException
{
    public function __construct() { parent::__construct('API key not found.'); }
}
