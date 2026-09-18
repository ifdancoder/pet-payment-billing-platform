<?php

namespace App\Shared\Application\Ports\Outbound;

interface IInboxPort
{
    /**
     * Records that this inbound event id has been processed. Returns
     * false — never throws — when it was already recorded, so a consumer
     * can treat a redelivered or duplicate message as a no-op instead of
     * doing the work twice.
     */
    public function recordIfNew(string $eventId, string $eventType): bool;
}
