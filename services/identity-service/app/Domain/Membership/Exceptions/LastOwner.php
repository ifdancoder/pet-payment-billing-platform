<?php

namespace App\Domain\Membership\Exceptions;

use RuntimeException;

final class LastOwner extends RuntimeException
{
    public function __construct() { parent::__construct('A merchant must retain at least one owner.'); }
}
