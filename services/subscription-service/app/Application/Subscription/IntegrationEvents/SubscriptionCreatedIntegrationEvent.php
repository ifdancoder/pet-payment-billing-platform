<?php

namespace App\Application\Subscription\IntegrationEvents;

use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class SubscriptionCreatedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $subscriptionId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly string $priceId,
        private readonly string $productId,
        private readonly int $amountMinorUnits,
        private readonly string $currency,
        private readonly string $billingInterval,
        private readonly int $billingIntervalCount,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(SubscriptionCreated $event): self
    {
        $snapshot = $event->priceSnapshot;

        return new self(
            Uuid::uuid4()->toString(),
            $event->subscriptionId->toString(),
            $event->merchantId->toString(),
            $event->customerId->toString(),
            $snapshot->priceId()->toString(),
            $snapshot->productId()->toString(),
            $snapshot->money()->amountMinorUnits(),
            $snapshot->money()->currency()->value,
            $snapshot->billingPeriod()->interval()->label(),
            $snapshot->billingPeriod()->count(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'subscription.created.v1';
    }

    public function aggregateType(): string
    {
        return 'subscription';
    }

    public function aggregateId(): string
    {
        return $this->subscriptionId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, string|int>
     */
    public function payload(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'price_id' => $this->priceId,
            'product_id' => $this->productId,
            'amount_minor_units' => $this->amountMinorUnits,
            'currency' => $this->currency,
            'billing_interval' => $this->billingInterval,
            'billing_interval_count' => $this->billingIntervalCount,
        ];
    }
}
