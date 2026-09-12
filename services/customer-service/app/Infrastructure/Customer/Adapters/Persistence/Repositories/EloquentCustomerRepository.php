<?php

namespace App\Infrastructure\Customer\Adapters\Persistence\Repositories;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Models\CustomerModel;

final class EloquentCustomerRepository implements ICustomerRepositoryPort
{
    public function __construct(private readonly CustomerMapper $mapper) {}

    public function save(Customer $customer): void
    {
        $model = CustomerModel::query()->find($customer->id()->toString());

        $this->mapper->toModel($customer, $model)->save();
    }

    public function findById(CustomerId $id): ?Customer
    {
        $model = CustomerModel::query()->find($id->toString());

        return $model ? $this->mapper->toDomain($model) : null;
    }
}
