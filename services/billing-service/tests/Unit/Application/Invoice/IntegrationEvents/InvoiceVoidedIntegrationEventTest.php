<?php

use App\Application\Invoice\IntegrationEvents\InvoiceVoidedIntegrationEvent;
use App\Domain\Invoice\Events\InvoiceVoided;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $invoiceId = InvoiceId::generate();
    $domainEvent = new InvoiceVoided($invoiceId, new DateTimeImmutable);

    $integrationEvent = InvoiceVoidedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('invoice.voided.v1')
        ->and($integrationEvent->aggregateType())->toBe('invoice')
        ->and($integrationEvent->aggregateId())->toBe($invoiceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'invoice_id' => $invoiceId->toString(),
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new InvoiceVoided(InvoiceId::generate(), new DateTimeImmutable);

    $a = InvoiceVoidedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = InvoiceVoidedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
