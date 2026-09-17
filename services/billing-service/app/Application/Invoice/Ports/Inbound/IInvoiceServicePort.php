<?php

namespace App\Application\Invoice\Ports\Inbound;

use App\Application\Invoice\Queries\GetInvoice\GetInvoiceQuery;
use App\Application\Invoice\Queries\ListInvoices\ListInvoicesQuery;
use App\Domain\Invoice\Invoice;

interface IInvoiceServicePort
{
    public function getInvoice(GetInvoiceQuery $query): Invoice;

    /**
     * @return array<int, Invoice>
     */
    public function listInvoices(ListInvoicesQuery $query): array;
}
