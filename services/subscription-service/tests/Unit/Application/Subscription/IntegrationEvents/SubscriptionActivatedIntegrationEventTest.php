<?php

use App\Application\Subscription\IntegrationEvents\SubscriptionActivatedIntegrationEvent;
use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $subscriptionId = SubscriptionId::generate();
    $domainEvent = new SubscriptionActivated($subscriptionId);

    $integrationEvent = SubscriptionActivatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('subscription.activated.v1')
        ->and($integrationEvent->aggregateType())->toBe('subscription')
        ->and($integrationEvent->aggregateId())->toBe($subscriptionId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'subscription_id' => $subscriptionId->toString(),
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new SubscriptionActivated(SubscriptionId::generate());

    $a = SubscriptionActivatedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = SubscriptionActivatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
