<?php

namespace App\Domain\Customer\Exceptions;

use App\Domain\Customer\ValueObjects\CustomerId;
use RuntimeException;

final class CustomerNotFound extends RuntimeException
{
    public static function withId(CustomerId $id): self
    {
        return new self("Customer \"{$id->toString()}\" was not found.");
    }
}
