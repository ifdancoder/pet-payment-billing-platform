<?php

namespace App\Domain\Notification\Events;

use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class NotificationSent
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly NotificationId $notificationId,
        public readonly MerchantId $merchantId,
        public readonly DeliveryAttemptId $deliveryAttemptId,
        public readonly ProviderReference $providerReference,
        public readonly DateTimeImmutable $sentAt,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
