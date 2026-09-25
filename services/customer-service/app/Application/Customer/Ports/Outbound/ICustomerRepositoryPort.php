<?php

namespace App\Application\Customer\Ports\Outbound;

use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface ICustomerRepositoryPort
{
    public function save(Customer $customer): void;

    /**
     * @throws CustomerNotFound
     */
    public function get(CustomerId $id, MerchantId $merchantId): Customer;

    /**
     * @throws CustomerNotFound
     */
    public function delete(CustomerId $id, MerchantId $merchantId): void;

    /**
     * @return array<int, Customer>
     */
    public function all(MerchantId $merchantId): array;
}
