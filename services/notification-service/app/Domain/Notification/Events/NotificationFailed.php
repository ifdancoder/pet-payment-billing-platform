<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class NotificationFailed
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly NotificationId $notificationId,
        public readonly MerchantId $merchantId,
        public readonly DeliveryAttemptId $deliveryAttemptId,
        public readonly string $failureCode,
        public readonly DateTimeImmutable $failedAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
