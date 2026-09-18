<?php

namespace App\Domain\Notification\Exceptions;

use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use RuntimeException;

final class InvalidNotificationTransition extends RuntimeException
{
    public static function forAction(NotificationId $id, string $action, NotificationStatus $currentStatus): self
    {
        return new self("Notification \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
