<?php

namespace App\Application\Subscription\Ports\Outbound;

use App\Application\Subscription\DataTransferObjects\CustomerData;
use App\Domain\Subscription\ValueObjects\CustomerId;

interface ICustomerGatewayPort
{
    public function find(CustomerId $id): ?CustomerData;
}
