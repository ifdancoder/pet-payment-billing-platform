<?php

namespace App\Application\Notification\Queries\ListNotifications;

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\Notification;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ListNotificationsHandler
{
    public function __construct(private readonly INotificationRepositoryPort $repository) {}

    /**
     * @return array<int, Notification>
     */
    public function handle(ListNotificationsQuery $query): array
    {
        return $this->repository->all(MerchantId::fromString($query->merchantId));
    }
}
