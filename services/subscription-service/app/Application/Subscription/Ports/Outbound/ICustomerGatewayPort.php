<?php

namespace App\Application\Subscription\Ports\Outbound;

use App\Application\Subscription\DataTransferObjects\CustomerData;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface ICustomerGatewayPort
{
    public function find(MerchantId $merchantId, CustomerId $id): ?CustomerData;
}
