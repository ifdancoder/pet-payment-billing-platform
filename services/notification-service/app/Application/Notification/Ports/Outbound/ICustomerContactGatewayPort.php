<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Application\Notification\DataTransferObjects\CustomerContact;

/**
 * Scoped to exactly what this service needs — a recipient's contact
 * details — not a general-purpose customer-service client. $customerId
 * is a plain string, not a domain value object: it is correlation data
 * lifted from an upstream event's payload, never a concept the
 * Notification aggregate itself models.
 */
interface ICustomerContactGatewayPort
{
    public function find(string $customerId): ?CustomerContact;
}
