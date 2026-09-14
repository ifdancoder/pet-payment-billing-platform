<?php

namespace App\Application\Customer;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\CreateCustomer\CreateCustomerHandler;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Application\Customer\Queries\GetCustomer\GetCustomerHandler;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Domain\Customer\Customer;

final class CustomerService implements ICustomerServicePort
{
    public function __construct(
        private readonly CreateCustomerHandler $createCustomerHandler,
        private readonly GetCustomerHandler $getCustomerHandler,
    ) {}

    public function createCustomer(CreateCustomerCommand $command): Customer
    {
        return $this->createCustomerHandler->handle($command);
    }

    public function getCustomer(GetCustomerQuery $query): Customer
    {
        return $this->getCustomerHandler->handle($query);
    }
}
