<?php

namespace App\Application\Customer\IntegrationEvents;

use App\Domain\Customer\Events\CustomerCreated;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class CustomerCreatedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $customerId,
        private readonly string $merchantId,
        private readonly string $email,
        private readonly string $name,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(CustomerCreated $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->customerId->toString(),
            $event->merchantId->toString(),
            $event->email->toString(),
            $event->name->toString(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'customer.created.v1';
    }

    public function aggregateType(): string
    {
        return 'customer';
    }

    public function aggregateId(): string
    {
        return $this->customerId;
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
            'customer_id' => $this->customerId,
            'merchant_id' => $this->merchantId,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
