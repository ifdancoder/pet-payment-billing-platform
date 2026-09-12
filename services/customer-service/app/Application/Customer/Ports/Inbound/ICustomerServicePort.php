<?php

namespace App\Application\Customer\Ports\Inbound;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Domain\Customer\Customer;

interface ICustomerServicePort
{
    public function createCustomer(CreateCustomerCommand $command): Customer;
}
