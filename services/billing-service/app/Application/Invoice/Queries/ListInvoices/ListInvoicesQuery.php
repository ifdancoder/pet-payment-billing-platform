<?php

namespace App\Application\Invoice\Queries\ListInvoices;

final class ListInvoicesQuery
{
    public function __construct(public readonly string $merchantId) {}
}
