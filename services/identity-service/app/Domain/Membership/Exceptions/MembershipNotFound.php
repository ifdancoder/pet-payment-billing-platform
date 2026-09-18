<?php

namespace App\Domain\Membership\Exceptions;

use App\Domain\Membership\ValueObjects\MembershipId;
use RuntimeException;

final class MembershipNotFound extends RuntimeException
{
    public static function withId(MembershipId $id): self
    {
        return new self("Membership \"{$id->toString()}\" was not found.");
    }
}
