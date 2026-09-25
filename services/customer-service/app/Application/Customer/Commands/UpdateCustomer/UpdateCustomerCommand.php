<?php

namespace App\Application\Customer\Commands\UpdateCustomer;

final class UpdateCustomerCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
