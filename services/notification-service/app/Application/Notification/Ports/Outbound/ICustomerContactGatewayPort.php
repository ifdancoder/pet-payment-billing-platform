<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Application\Notification\DataTransferObjects\CustomerContact;

interface ICustomerContactGatewayPort
{
    public function find(string $merchantId, string $customerId): ?CustomerContact;
}
