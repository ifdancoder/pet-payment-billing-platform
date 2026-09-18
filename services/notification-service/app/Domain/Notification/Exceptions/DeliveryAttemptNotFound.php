<?php

namespace App\Domain\Notification\Exceptions;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use RuntimeException;

final class DeliveryAttemptNotFound extends RuntimeException
{
    public static function withId(DeliveryAttemptId $id): self
    {
        return new self("Delivery attempt \"{$id->toString()}\" was not found on this notification.");
    }
}
