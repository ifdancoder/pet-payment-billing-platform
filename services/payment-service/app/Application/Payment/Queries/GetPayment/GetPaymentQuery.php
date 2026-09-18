<?php

namespace App\Application\Payment\Queries\GetPayment;

final class GetPaymentQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
    ) {}
}
