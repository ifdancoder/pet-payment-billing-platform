<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Invoice\Invoice;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class InvoiceResourceCollection extends ResourceCollection
{
    public $collects = InvoiceResource::class;

    /**
     * @param  array<int, Invoice>  $invoices
     */
    public function __construct(array $invoices)
    {
        parent::__construct($invoices);
    }
}
