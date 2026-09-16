<?php

namespace App\Domain\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\SubscriptionId;
use RuntimeException;

final class SubscriptionNotFound extends RuntimeException
{
    public static function withId(SubscriptionId $id): self
    {
        return new self("Subscription \"{$id->toString()}\" was not found.");
    }
}
