<?php

namespace App\Application\Invoice\Queries\ListInvoices;

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Invoice;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ListInvoicesHandler
{
    public function __construct(private readonly IInvoiceRepositoryPort $repository) {}

    /**
     * @return array<int, Invoice>
     */
    public function handle(ListInvoicesQuery $query): array
    {
        return $this->repository->all(MerchantId::fromString($query->merchantId));
    }
}
