<?php

namespace App\Presentation\Invoice\Adapters\Inbound\Http\Controllers;

use App\Application\Invoice\Commands\VoidInvoice\VoidInvoiceCommand;
use App\Application\Invoice\Ports\Inbound\IInvoiceServicePort;
use App\Application\Invoice\Queries\GetInvoice\GetInvoiceQuery;
use App\Application\Invoice\Queries\ListInvoices\ListInvoicesQuery;
use App\Presentation\Invoice\Adapters\Inbound\Http\Resources\InvoiceResource;
use App\Presentation\Invoice\Adapters\Inbound\Http\Resources\InvoiceResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class InvoiceController extends Controller
{
    public function __construct(private readonly IInvoiceServicePort $invoiceService) {}

    public function index(string $merchant): JsonResponse
    {
        $invoices = $this->invoiceService->listInvoices(new ListInvoicesQuery($merchant));

        return (new InvoiceResourceCollection($invoices))->response();
    }

    public function show(string $merchant, string $invoice): JsonResponse
    {
        $found = $this->invoiceService->getInvoice(new GetInvoiceQuery($merchant, $invoice));

        return InvoiceResource::make($found)->response();
    }

    public function void(string $merchant, string $invoice): JsonResponse
    {
        $voided = $this->invoiceService->voidInvoice(new VoidInvoiceCommand($merchant, $invoice));

        return InvoiceResource::make($voided)->response();
    }
}
