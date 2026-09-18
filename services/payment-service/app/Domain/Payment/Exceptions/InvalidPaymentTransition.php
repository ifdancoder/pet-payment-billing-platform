<?php

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use RuntimeException;

final class InvalidPaymentTransition extends RuntimeException
{
    public static function forAction(PaymentId $id, string $action, PaymentStatus $currentStatus): self
    {
        return new self("Payment \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
