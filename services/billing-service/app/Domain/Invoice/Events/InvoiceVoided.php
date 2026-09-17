<?php

namespace App\Domain\Invoice\Events;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use DateTimeImmutable;

final class InvoiceVoided
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly DateTimeImmutable $voidedAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
