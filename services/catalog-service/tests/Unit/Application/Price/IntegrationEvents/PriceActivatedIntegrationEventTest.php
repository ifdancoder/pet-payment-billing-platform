<?php

use App\Application\Price\IntegrationEvents\PriceActivatedIntegrationEvent;
use App\Domain\Price\Events\PriceActivated;
use App\Domain\Price\ValueObjects\PriceId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $priceId = PriceId::generate();
    $domainEvent = new PriceActivated($priceId);

    $integrationEvent = PriceActivatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('price.activated.v1')
        ->and($integrationEvent->aggregateType())->toBe('price')
        ->and($integrationEvent->aggregateId())->toBe($priceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe(['price_id' => $priceId->toString()]);
});
