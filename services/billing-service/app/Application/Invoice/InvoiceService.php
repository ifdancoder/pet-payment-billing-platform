<?php

namespace App\Application\Invoice;

use App\Application\Invoice\Commands\VoidInvoice\VoidInvoiceCommand;
use App\Application\Invoice\Commands\VoidInvoice\VoidInvoiceHandler;
use App\Application\Invoice\Ports\Inbound\IInvoiceServicePort;
use App\Application\Invoice\Queries\GetInvoice\GetInvoiceHandler;
use App\Application\Invoice\Queries\GetInvoice\GetInvoiceQuery;
use App\Application\Invoice\Queries\ListInvoices\ListInvoicesHandler;
use App\Application\Invoice\Queries\ListInvoices\ListInvoicesQuery;
use App\Domain\Invoice\Invoice;

final class InvoiceService implements IInvoiceServicePort
{
    public function __construct(
        private readonly GetInvoiceHandler $getInvoiceHandler,
        private readonly ListInvoicesHandler $listInvoicesHandler,
        private readonly VoidInvoiceHandler $voidInvoiceHandler,
    ) {}

    public function getInvoice(GetInvoiceQuery $query): Invoice
    {
        return $this->getInvoiceHandler->handle($query);
    }

    public function listInvoices(ListInvoicesQuery $query): array
    {
        return $this->listInvoicesHandler->handle($query);
    }

    public function voidInvoice(VoidInvoiceCommand $command): Invoice
    {
        return $this->voidInvoiceHandler->handle($command);
    }
}
