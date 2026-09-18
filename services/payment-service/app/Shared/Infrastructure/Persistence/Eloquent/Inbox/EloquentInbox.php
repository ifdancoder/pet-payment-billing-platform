<?php

namespace App\Shared\Infrastructure\Persistence\Eloquent\Inbox;

use App\Shared\Application\Ports\Outbound\IInboxPort;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentInbox implements IInboxPort
{
    public function recordIfNew(string $eventId, string $eventType): bool
    {
        try {
            InboxMessageModel::query()->create([
                'id' => $eventId,
                'event_type' => $eventType,
                'processed_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
