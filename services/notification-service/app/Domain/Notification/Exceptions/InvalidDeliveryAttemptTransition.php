<?php

namespace App\Domain\Notification\Exceptions;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\DeliveryAttemptStatus;
use RuntimeException;

final class InvalidDeliveryAttemptTransition extends RuntimeException
{
    public static function forAction(DeliveryAttemptId $id, string $action, DeliveryAttemptStatus $currentStatus): self
    {
        return new self("Delivery attempt \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
