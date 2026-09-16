<?php

namespace App\Application\Subscription\DataTransferObjects;

final class CustomerData
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
