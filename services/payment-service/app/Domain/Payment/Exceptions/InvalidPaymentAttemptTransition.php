<?php

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentAttemptStatus;
use RuntimeException;

final class InvalidPaymentAttemptTransition extends RuntimeException
{
    public static function forAction(PaymentAttemptId $id, string $action, PaymentAttemptStatus $currentStatus): self
    {
        return new self("Payment attempt \"{$id->toString()}\" cannot {$action} while in status \"{$currentStatus->label()}\".");
    }
}
