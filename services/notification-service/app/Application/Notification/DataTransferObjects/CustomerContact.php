<?php

namespace App\Application\Notification\DataTransferObjects;

/**
 * What this service needs to know about a customer, translated from
 * customer-service's own wire format. Never the customer-service
 * Customer domain object itself — this is the anti-corruption boundary.
 */
final class CustomerContact
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
