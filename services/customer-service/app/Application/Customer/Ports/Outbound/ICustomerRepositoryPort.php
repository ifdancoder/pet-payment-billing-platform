<?php

namespace App\Application\Customer\Ports\Outbound;

use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;

interface ICustomerRepositoryPort
{
    public function save(Customer $customer): void;

    /**
     * @throws CustomerNotFound
     */
    public function get(CustomerId $id): Customer;
}
