<?php

namespace App\Domain\Invoice\Events;

use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class InvoiceCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly MerchantId $merchantId,
        public readonly CustomerId $customerId,
        public readonly SubscriptionId $subscriptionId,
        public readonly BillingPeriod $period,
        public readonly Money $subtotal,
        public readonly Money $total,
        public readonly bool $renewal = false,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
