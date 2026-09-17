<?php

namespace App\Application\Invoice\Commands\VoidInvoice;

final class VoidInvoiceCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $invoiceId,
    ) {}
}
