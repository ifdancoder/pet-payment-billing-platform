<?php

namespace App\Application\Customer\Queries\GetCustomer;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;

final class GetCustomerHandler
{
    public function __construct(private readonly ICustomerRepositoryPort $repository) {}

    public function handle(GetCustomerQuery $query): Customer
    {
        return $this->repository->get(CustomerId::fromString($query->id));
    }
}
