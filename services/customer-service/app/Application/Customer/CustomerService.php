<?php

namespace App\Application\Customer;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\CreateCustomer\CreateCustomerHandler;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Domain\Customer\ValueObjects\CustomerId;

final class CustomerService implements ICustomerServicePort
{
    public function __construct(private readonly CreateCustomerHandler $createCustomerHandler) {}

    public function createCustomer(CreateCustomerCommand $command): CustomerId
    {
        return $this->createCustomerHandler->handle($command);
    }
}
