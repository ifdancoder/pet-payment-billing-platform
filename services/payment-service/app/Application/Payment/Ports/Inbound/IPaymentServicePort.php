<?php

namespace App\Application\Payment\Ports\Inbound;

use App\Application\Payment\Queries\GetPayment\GetPaymentQuery;
use App\Application\Payment\Queries\ListPayments\ListPaymentsQuery;
use App\Domain\Payment\Payment;

interface IPaymentServicePort
{
    public function getPayment(GetPaymentQuery $query): Payment;

    /**
     * @return array<int, Payment>
     */
    public function listPayments(ListPaymentsQuery $query): array;
}
