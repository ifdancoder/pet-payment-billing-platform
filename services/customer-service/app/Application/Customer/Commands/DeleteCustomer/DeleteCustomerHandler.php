<?php

namespace App\Application\Customer\Commands\DeleteCustomer;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\ValueObjects\CustomerId;

final class DeleteCustomerHandler
{
    public function __construct(private readonly ICustomerRepositoryPort $repository) {}

    public function handle(DeleteCustomerCommand $command): void
    {
        $this->repository->delete(CustomerId::fromString($command->id));
    }
}
