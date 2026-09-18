<?php

namespace App\Application\Payment\Commands\ProcessPayment;

final class ProcessPaymentCommand
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $merchantId,
    ) {}
}
