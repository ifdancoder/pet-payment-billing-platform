<?php

namespace App\Infrastructure\Customer\Adapters\Persistence\Repositories;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Models\CustomerModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentCustomerRepository implements ICustomerRepositoryPort
{
    public function __construct(private readonly CustomerMapper $mapper) {}

    public function save(Customer $customer): void
    {
        $model = CustomerModel::query()->find($customer->id()->toString());

        $this->mapper->toModel($customer, $model)->save();
    }

    public function get(CustomerId $id, MerchantId $merchantId): Customer
    {
        $model = CustomerModel::query()
            ->whereKey($id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw CustomerNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function delete(CustomerId $id, MerchantId $merchantId): void
    {
        $deleted = CustomerModel::query()
            ->whereKey($id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->delete();

        if ($deleted === 0) {
            throw CustomerNotFound::withId($id);
        }
    }

    public function all(MerchantId $merchantId): array
    {
        return CustomerModel::query()
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (CustomerModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
