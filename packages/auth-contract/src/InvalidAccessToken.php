<?php

namespace Platform\Auth;

use RuntimeException;

final class InvalidAccessToken extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
