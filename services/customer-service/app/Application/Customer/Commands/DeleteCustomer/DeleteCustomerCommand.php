<?php

namespace App\Application\Customer\Commands\DeleteCustomer;

final class DeleteCustomerCommand
{
    public function __construct(public readonly string $merchantId, public readonly string $id) {}
}
