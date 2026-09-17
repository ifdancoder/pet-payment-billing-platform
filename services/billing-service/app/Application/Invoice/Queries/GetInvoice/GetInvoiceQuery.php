<?php

namespace App\Application\Invoice\Queries\GetInvoice;

final class GetInvoiceQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
    ) {}
}
