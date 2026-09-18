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

    public function findByDeduplicationKey(string $deduplicationKey): ?Notification;

    /**
     * Unscoped internal worker query.
     *
     * @return array<int, Notification>
     */
    public function pending(): array;
}
