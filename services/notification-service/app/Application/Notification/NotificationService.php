<?php

namespace App\Application\Notification;

use App\Application\Notification\Ports\Inbound\INotificationServicePort;
use App\Application\Notification\Queries\GetNotification\GetNotificationHandler;
use App\Application\Notification\Queries\GetNotification\GetNotificationQuery;
use App\Application\Notification\Queries\ListNotifications\ListNotificationsHandler;
use App\Application\Notification\Queries\ListNotifications\ListNotificationsQuery;
use App\Domain\Notification\Notification;

final class NotificationService implements INotificationServicePort
{
    public function __construct(
        private readonly GetNotificationHandler $getNotificationHandler,
        private readonly ListNotificationsHandler $listNotificationsHandler,
    ) {}

    public function getNotification(GetNotificationQuery $query): Notification
    {
        return $this->getNotificationHandler->handle($query);
    }

    public function listNotifications(ListNotificationsQuery $query): array
    {
        return $this->listNotificationsHandler->handle($query);
    }
}
