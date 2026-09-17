<?php

namespace App\Application\Invoice\Queries\GetInvoice;

use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Shared\Domain\ValueObjects\MerchantId;

final class GetInvoiceHandler
{
    public function __construct(private readonly IInvoiceRepositoryPort $repository) {}

    public function handle(GetInvoiceQuery $query): Invoice
    {
        return $this->repository->get(
            InvoiceId::fromString($query->id),
            MerchantId::fromString($query->merchantId),
        );
    }
}
