<?php

namespace App\Application\Customer\Commands\UpdateCustomer;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;

final class UpdateCustomerHandler
{
    public function __construct(private readonly ICustomerRepositoryPort $repository) {}

    public function handle(UpdateCustomerCommand $command): Customer
    {
        $customer = $this->repository->get(CustomerId::fromString($command->id));

        $customer->changeEmail(Email::fromString($command->email));
        $customer->rename(CustomerName::fromString($command->name));

        $this->repository->save($customer);

        return $customer;
    }
}
