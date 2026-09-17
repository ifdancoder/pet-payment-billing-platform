<?php

namespace App\Application\Invoice\Ports\Outbound;

use App\Domain\Invoice\Exceptions\InvoiceNotFound;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

interface IInvoiceRepositoryPort
{
    public function save(Invoice $invoice): void;

    /**
     * @throws InvoiceNotFound
     */
    public function get(InvoiceId $id, MerchantId $merchantId): Invoice;

    /**
     * @return array<int, Invoice>
     */
    public function all(MerchantId $merchantId): array;

    public function findByBillingCycle(SubscriptionId $subscriptionId, DateTimeImmutable $periodStart): ?Invoice;
}
