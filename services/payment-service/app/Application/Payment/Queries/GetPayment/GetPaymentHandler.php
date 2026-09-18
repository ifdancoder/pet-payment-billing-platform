<?php

namespace App\Application\Payment\Queries\GetPayment;

use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Shared\Domain\ValueObjects\MerchantId;

final class GetPaymentHandler
{
    public function __construct(private readonly IPaymentRepositoryPort $repository) {}

    public function handle(GetPaymentQuery $query): Payment
    {
        return $this->repository->get(
            PaymentId::fromString($query->id),
            MerchantId::fromString($query->merchantId),
        );
    }
}
