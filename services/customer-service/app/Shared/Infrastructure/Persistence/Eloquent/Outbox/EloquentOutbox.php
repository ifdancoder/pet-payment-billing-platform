<?php

namespace App\Shared\Infrastructure\Persistence\Eloquent\Outbox;

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\ReadModels\OutboxMessage;

final class EloquentOutbox implements IOutboxPort
{
    public function add(IntegrationEvent $event): void
    {
        OutboxMessageModel::query()->create([
            'id' => $event->eventId(),
            'event_type' => $event->eventType(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'payload' => $event->payload(),
            'occurred_at' => $event->occurredAt(),
        ]);
    }

    public function unpublished(): array
    {
        return OutboxMessageModel::query()
            ->whereNull('published_at')
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (OutboxMessageModel $model) => new OutboxMessage(
                $model->id,
                $model->event_type,
                $model->aggregate_type,
                $model->aggregate_id,
                $model->payload,
                $model->occurred_at->toDateTimeImmutable(),
            ))
            ->all();
    }

    public function markPublished(string $eventId): void
    {
        OutboxMessageModel::query()
            ->where('id', $eventId)
            ->update(['published_at' => now()]);
    }
}
