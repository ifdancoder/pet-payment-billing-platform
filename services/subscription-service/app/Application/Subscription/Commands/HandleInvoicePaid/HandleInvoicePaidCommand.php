<?php

namespace App\Application\Subscription\Commands\HandleInvoicePaid;

final class HandleInvoicePaidCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $subscriptionId,
        public readonly string $merchantId,
    ) {}
}
