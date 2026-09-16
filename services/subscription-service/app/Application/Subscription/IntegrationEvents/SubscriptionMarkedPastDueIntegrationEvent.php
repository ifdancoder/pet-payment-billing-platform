<?php

namespace App\Application\Subscription\IntegrationEvents;

use App\Domain\Subscription\Events\SubscriptionMarkedPastDue;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class SubscriptionMarkedPastDueIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $subscriptionId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(SubscriptionMarkedPastDue $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->subscriptionId->toString(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'subscription.past_due.v1';
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
     * @return array<string, string>
     */
    public function payload(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
        ];
    }
}
