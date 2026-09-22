<?php

namespace App\Infrastructure\Invoice\Adapters\Messaging\Consumers;

use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceCommand;
use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceHandler;
use DateTimeImmutable;

final class SubscriptionRenewalDueConsumer
{
    public function __construct(private readonly CreateInvoiceHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new CreateInvoiceCommand(
            $eventId,
            'subscription.renewal_due.v1',
            $payload['merchant_id'],
            $payload['customer_id'],
            $payload['subscription_id'],
            $payload['product_id'] ?? null,
            $payload['price_id'] ?? null,
            'Subscription renewal',
            $payload['amount_minor_units'],
            $payload['currency'],
            $payload['billing_interval'],
            $payload['billing_interval_count'],
            new DateTimeImmutable($payload['period_start']),
            true,
        ));
    }
}
