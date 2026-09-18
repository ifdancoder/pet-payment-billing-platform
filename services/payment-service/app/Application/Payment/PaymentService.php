<?php

namespace App\Application\Payment;

use App\Application\Payment\Ports\Inbound\IPaymentServicePort;
use App\Application\Payment\Queries\GetPayment\GetPaymentHandler;
use App\Application\Payment\Queries\GetPayment\GetPaymentQuery;
use App\Application\Payment\Queries\ListPayments\ListPaymentsHandler;
use App\Application\Payment\Queries\ListPayments\ListPaymentsQuery;
use App\Domain\Payment\Payment;

final class PaymentService implements IPaymentServicePort
{
    public function __construct(
        private readonly GetPaymentHandler $getPaymentHandler,
        private readonly ListPaymentsHandler $listPaymentsHandler,
    ) {}

    public function getPayment(GetPaymentQuery $query): Payment
    {
        return $this->getPaymentHandler->handle($query);
    }

    public function listPayments(ListPaymentsQuery $query): array
    {
        return $this->listPaymentsHandler->handle($query);
    }
}
