<?php

use App\Application\Product\IntegrationEvents\ProductCreatedIntegrationEvent;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $productId = ProductId::generate();
    $domainEvent = new ProductCreated($productId, ProductName::fromString('Pro Plan'));

    $integrationEvent = ProductCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('product.created.v1')
        ->and($integrationEvent->aggregateType())->toBe('product')
        ->and($integrationEvent->aggregateId())->toBe($productId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'product_id' => $productId->toString(),
            'name' => 'Pro Plan',
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new ProductCreated(ProductId::generate(), ProductName::fromString('Pro Plan'));

    $a = ProductCreatedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = ProductCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
