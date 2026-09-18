<?php

namespace App\Application\Notification\Commands\CreateNotification;

final class CreateNotificationCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $merchantId,
        public readonly int $type,
        public readonly int $channel,
        public readonly string $recipientEmail,
        public readonly string $subject,
        public readonly string $bodyText,
        public readonly string $bodyHtml,
        public readonly string $deduplicationKey,
    ) {}
}
