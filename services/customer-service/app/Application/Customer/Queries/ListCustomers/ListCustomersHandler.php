<?php

namespace App\Application\Customer\Queries\ListCustomers;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Customer;

final class ListCustomersHandler
{
    public function __construct(private readonly ICustomerRepositoryPort $repository) {}

    /**
     * @return array<int, Customer>
     */
    public function handle(ListCustomersQuery $query): array
    {
        return $this->repository->all();
    }
}
