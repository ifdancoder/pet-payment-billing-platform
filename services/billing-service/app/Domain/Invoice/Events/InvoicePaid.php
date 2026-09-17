<?php

namespace App\Domain\Invoice\Events;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\PaymentId;
use DateTimeImmutable;

final class InvoicePaid
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly PaymentId $paymentId,
        public readonly DateTimeImmutable $paidAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
