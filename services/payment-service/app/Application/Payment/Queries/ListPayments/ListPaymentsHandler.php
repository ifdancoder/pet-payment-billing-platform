<?php

namespace App\Application\Payment\Queries\ListPayments;

use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Payment;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ListPaymentsHandler
{
    public function __construct(private readonly IPaymentRepositoryPort $repository) {}

    /**
     * @return array<int, Payment>
     */
    public function handle(ListPaymentsQuery $query): array
    {
        return $this->repository->all(MerchantId::fromString($query->merchantId));
    }
}
