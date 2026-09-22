<?php

namespace App\Application\Subscription\IntegrationEvents;

use App\Domain\Subscription\Events\SubscriptionRenewalDue;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class SubscriptionRenewalDueIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly SubscriptionRenewalDue $event,
    ) {}

    public static function fromDomainEvent(SubscriptionRenewalDue $event): self
    {
        return new self(Uuid::uuid4()->toString(), $event);
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'subscription.renewal_due.v1';
    }

    public function aggregateType(): string
    {
        return 'subscription';
    }

    public function aggregateId(): string
    {
        return $this->event->subscriptionId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->event->occurredAt;
    }

    /**
     * @return array<string, string|int>
     */
    public function payload(): array
    {
        $snapshot = $this->event->priceSnapshot;

        return [
            'subscription_id' => $this->event->subscriptionId->toString(),
            'merchant_id' => $this->event->merchantId->toString(),
            'customer_id' => $this->event->customerId->toString(),
            'price_id' => $snapshot->priceId()->toString(),
            'product_id' => $snapshot->productId()->toString(),
            'amount_minor_units' => $snapshot->money()->amountMinorUnits(),
            'currency' => $snapshot->money()->currency()->value,
            'billing_interval' => $snapshot->billingPeriod()->interval()->label(),
            'billing_interval_count' => $snapshot->billingPeriod()->count(),
            'period_start' => $this->event->periodStart->format(DATE_ATOM),
        ];
    }
}
