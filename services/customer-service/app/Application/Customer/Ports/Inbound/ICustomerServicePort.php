<?php

namespace App\Application\Customer\Ports\Inbound;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\DeleteCustomer\DeleteCustomerCommand;
use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerCommand;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;

interface ICustomerServicePort
{
    public function createCustomer(CreateCustomerCommand $command): Customer;

    /**
     * @throws CustomerNotFound
     */
    public function getCustomer(GetCustomerQuery $query): Customer;

    /**
     * @throws CustomerNotFound
     */
    public function updateCustomer(UpdateCustomerCommand $command): Customer;

    /**
     * @throws CustomerNotFound
     */
    public function deleteCustomer(DeleteCustomerCommand $command): void;
}
