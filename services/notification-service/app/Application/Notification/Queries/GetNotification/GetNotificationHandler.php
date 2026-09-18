<?php

namespace App\Application\Notification\Queries\GetNotification;

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Shared\Domain\ValueObjects\MerchantId;

final class GetNotificationHandler
{
    public function __construct(private readonly INotificationRepositoryPort $repository) {}

    public function handle(GetNotificationQuery $query): Notification
    {
        return $this->repository->get(
            NotificationId::fromString($query->id),
            MerchantId::fromString($query->merchantId),
        );
    }
}
