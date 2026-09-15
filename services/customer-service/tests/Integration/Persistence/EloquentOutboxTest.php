<?php

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\EloquentOutbox;
use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\OutboxMessageModel;
use DateTimeImmutable;

final class FakeIntegrationEvent implements IntegrationEvent
{
    public function __construct(
        private readonly string $eventId,
        private readonly string $aggregateId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
    ) {}

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
        return $this->aggregateId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function payload(): array
    {
        return ['email' => 'jane@example.com'];
    }
}

test('add persists a new outbox message', function () {
    $outbox = new EloquentOutbox;
    $event = new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345', '1a2b3c4d-5e6f-4321-8765-0123456789ab');

    $outbox->add($event);

    $model = OutboxMessageModel::query()->find('9f8e7d6c-5b4a-4321-9876-abcdef012345');
    expect($model)->not->toBeNull()
        ->and($model->event_type)->toBe('customer.created.v1')
        ->and($model->aggregate_type)->toBe('customer')
        ->and($model->aggregate_id)->toBe('1a2b3c4d-5e6f-4321-8765-0123456789ab')
        ->and($model->payload)->toBe(['email' => 'jane@example.com'])
        ->and($model->published_at)->toBeNull();
});

test('unpublished returns only messages that have not been published', function () {
    $outbox = new EloquentOutbox;
    $outbox->add(new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345', '1a2b3c4d-5e6f-4321-8765-0123456789ab'));
    $outbox->add(new FakeIntegrationEvent('2b3c4d5e-6f70-4321-8765-0123456789ab', '1a2b3c4d-5e6f-4321-8765-0123456789ab'));
    $outbox->markPublished('9f8e7d6c-5b4a-4321-9876-abcdef012345');

    $unpublished = $outbox->unpublished();

    expect($unpublished)->toHaveCount(1)
        ->and($unpublished[0]->eventId)->toBe('2b3c4d5e-6f70-4321-8765-0123456789ab')
        ->and($unpublished[0]->eventType)->toBe('customer.created.v1')
        ->and($unpublished[0]->payload)->toBe(['email' => 'jane@example.com']);
});

test('markPublished sets the published_at timestamp', function () {
    $outbox = new EloquentOutbox;
    $outbox->add(new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345', '1a2b3c4d-5e6f-4321-8765-0123456789ab'));

    $outbox->markPublished('9f8e7d6c-5b4a-4321-9876-abcdef012345');

    $model = OutboxMessageModel::query()->find('9f8e7d6c-5b4a-4321-9876-abcdef012345');
    expect($model->published_at)->not->toBeNull();
});
