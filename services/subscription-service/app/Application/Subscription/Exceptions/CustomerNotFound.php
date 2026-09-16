<?php

namespace App\Application\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\CustomerId;
use RuntimeException;

final class CustomerNotFound extends RuntimeException
{
    public static function withId(CustomerId $id): self
    {
        return new self("Customer \"{$id->toString()}\" was not found.");
    }
}
