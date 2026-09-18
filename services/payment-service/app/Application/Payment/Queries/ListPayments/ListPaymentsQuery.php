<?php

namespace App\Application\Payment\Queries\ListPayments;

final class ListPaymentsQuery
{
    public function __construct(public readonly string $merchantId) {}
}
