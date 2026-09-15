<?php

use App\Application\Product\IntegrationEvents\ProductArchivedIntegrationEvent;
use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\ValueObjects\ProductId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $productId = ProductId::generate();
    $domainEvent = new ProductArchived($productId);

    $integrationEvent = ProductArchivedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('product.archived.v1')
        ->and($integrationEvent->aggregateType())->toBe('product')
        ->and($integrationEvent->aggregateId())->toBe($productId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'product_id' => $productId->toString(),
        ]);
});
