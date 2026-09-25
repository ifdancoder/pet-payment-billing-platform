<?php

namespace App\Application\Customer\Queries\ListCustomers;

final class ListCustomersQuery
{
    public function __construct(public readonly string $merchantId) {}
}
