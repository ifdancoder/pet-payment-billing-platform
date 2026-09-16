<?php

namespace App\Domain\Subscription\Exceptions;

use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use RuntimeException;

final class InvalidSubscriptionTransition extends RuntimeException
{
    public static function forAction(SubscriptionId $id, string $action, SubscriptionStatus $currentStatus): self
    {
        return new self("Subscription \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
