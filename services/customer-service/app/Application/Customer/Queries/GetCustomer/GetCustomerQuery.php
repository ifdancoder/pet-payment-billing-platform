<?php

namespace App\Application\Customer\Queries\GetCustomer;

final class GetCustomerQuery
{
    public function __construct(public readonly string $merchantId, public readonly string $id) {}
}
