<?php

namespace App\Application\Notification\DataTransferObjects;

final class CustomerContact
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
