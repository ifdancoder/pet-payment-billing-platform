<?php

namespace App\Domain\Notification\Exceptions;

use App\Domain\Notification\ValueObjects\NotificationId;
use RuntimeException;

final class NotificationAlreadySent extends RuntimeException
{
    public static function withId(NotificationId $id): self
    {
        return new self("Notification \"{$id->toString()}\" has already been sent and cannot be retried.");
    }
}
