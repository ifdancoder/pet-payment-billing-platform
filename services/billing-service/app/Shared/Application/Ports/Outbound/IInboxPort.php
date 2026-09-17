<?php

namespace App\Shared\Application\Ports\Outbound;

interface IInboxPort
{
    /** Returns false when the event ID was already recorded. */
    public function recordIfNew(string $eventId, string $eventType): bool;
}
