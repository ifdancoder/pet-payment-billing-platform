<?php

use App\Application\Customer\IntegrationEvents\CustomerCreatedIntegrationEvent;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $customerId = CustomerId::generate();
    $domainEvent = new CustomerCreated($customerId, Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $integrationEvent = CustomerCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('customer.created.v1')
        ->and($integrationEvent->aggregateType())->toBe('customer')
        ->and($integrationEvent->aggregateId())->toBe($customerId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'customer_id' => $customerId->toString(),
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new CustomerCreated(CustomerId::generate(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $a = CustomerCreatedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = CustomerCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
