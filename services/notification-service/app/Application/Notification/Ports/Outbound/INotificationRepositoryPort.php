<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Domain\Notification\Exceptions\NotificationNotFound;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface INotificationRepositoryPort
{
    public function save(Notification $notification): void;

    /**
     * @throws NotificationNotFound
     */
    public function get(NotificationId $id, MerchantId $merchantId): Notification;

    /**
     * @return array<int, Notification>
     */
    public function all(MerchantId $merchantId): array;

    /**
     * Looks up the notification already created for this deduplication
     * key, so a handler can treat a redelivered or duplicate trigger as a
     * no-op instead of creating a second notification for the same
     * underlying business fact.
     */
    public function findByDeduplicationKey(string $deduplicationKey): ?Notification;
}
