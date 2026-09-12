<?php

namespace App\Infrastructure\Customer\Adapters\Persistence\Mappers;

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Models\CustomerModel;

final class CustomerMapper
{
    public function toDomain(CustomerModel $model): Customer
    {
        return Customer::reconstitute(
            CustomerId::fromString($model->id),
            Email::fromString($model->email),
            CustomerName::fromString($model->name),
        );
    }

    public function toModel(Customer $customer, ?CustomerModel $model = null): CustomerModel
    {
        $model ??= new CustomerModel;

        $model->id = $customer->id()->toString();
        $model->email = $customer->email()->toString();
        $model->name = $customer->name()->toString();

        return $model;
    }
}
