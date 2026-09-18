<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class NotificationCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly NotificationId $notificationId,
        public readonly MerchantId $merchantId,
        public readonly string $sourceEventId,
        public readonly NotificationType $type,
        public readonly NotificationChannel $channel,
        public readonly EmailAddress $recipient,
        public readonly string $deduplicationKey,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
