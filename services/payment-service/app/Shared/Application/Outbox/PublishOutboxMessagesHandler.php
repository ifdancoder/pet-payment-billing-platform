<?php

namespace App\Shared\Application\Outbox;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;

final class PublishOutboxMessagesHandler
{
    public function __construct(
        private readonly IOutboxPort $outbox,
        private readonly IEventPublisherPort $publisher,
    ) {}

    public function handle(): int
    {
        $published = 0;

        foreach ($this->outbox->unpublished() as $message) {
            $this->publisher->publish($message);
            $this->outbox->markPublished($message->eventId);
            $published++;
        }

        return $published;
    }
}
