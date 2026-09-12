<?php

namespace App\Application\Customer\Commands\CreateCustomer;

final class CreateCustomerCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}
}
