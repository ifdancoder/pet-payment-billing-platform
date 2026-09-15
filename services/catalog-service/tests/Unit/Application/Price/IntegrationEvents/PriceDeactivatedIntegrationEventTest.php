<?php

use App\Application\Price\IntegrationEvents\PriceDeactivatedIntegrationEvent;
use App\Domain\Price\Events\PriceDeactivated;
use App\Domain\Price\ValueObjects\PriceId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $priceId = PriceId::generate();
    $domainEvent = new PriceDeactivated($priceId);

    $integrationEvent = PriceDeactivatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('price.deactivated.v1')
        ->and($integrationEvent->aggregateType())->toBe('price')
        ->and($integrationEvent->aggregateId())->toBe($priceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe(['price_id' => $priceId->toString()]);
});
