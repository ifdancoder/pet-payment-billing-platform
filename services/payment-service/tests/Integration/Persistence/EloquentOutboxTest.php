<?php

use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\EloquentOutbox;
use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\OutboxMessageModel;
use Tests\Support\FakeIntegrationEvent;

test('add persists a new outbox message', function () {
    $outbox = new EloquentOutbox;
    $event = new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345', '1a2b3c4d-5e6f-4321-8765-0123456789ab');

    $outbox->add($event);

    $model = OutboxMessageModel::query()->find('9f8e7d6c-5b4a-4321-9876-abcdef012345');
    expect($model)->not->toBeNull()
        ->and($model->event_type)->toBe('payment.succeeded.v1')
        ->and($model->aggregate_type)->toBe('payment')
        ->and($model->aggregate_id)->toBe('1a2b3c4d-5e6f-4321-8765-0123456789ab')
        ->and($model->payload)->toBe(['status' => 'succeeded'])
        ->and($model->published_at)->toBeNull();
});

test('unpublished returns only messages that have not been published', function () {
    $outbox = new EloquentOutbox;
    $outbox->add(new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345'));
    $outbox->add(new FakeIntegrationEvent('2b3c4d5e-6f70-4321-8765-0123456789ab'));
    $outbox->markPublished('9f8e7d6c-5b4a-4321-9876-abcdef012345');

    $unpublished = $outbox->unpublished();

    expect($unpublished)->toHaveCount(1)
        ->and($unpublished[0]->eventId)->toBe('2b3c4d5e-6f70-4321-8765-0123456789ab')
        ->and($unpublished[0]->eventType)->toBe('payment.succeeded.v1')
        ->and($unpublished[0]->payload)->toBe(['status' => 'succeeded']);
});

test('markPublished sets the published_at timestamp', function () {
    $outbox = new EloquentOutbox;
    $outbox->add(new FakeIntegrationEvent('9f8e7d6c-5b4a-4321-9876-abcdef012345'));

    $outbox->markPublished('9f8e7d6c-5b4a-4321-9876-abcdef012345');

    $model = OutboxMessageModel::query()->find('9f8e7d6c-5b4a-4321-9876-abcdef012345');
    expect($model->published_at)->not->toBeNull();
});
