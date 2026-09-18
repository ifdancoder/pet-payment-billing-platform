<?php

namespace App\Application\Notification\Ports\Inbound;

use App\Application\Notification\Queries\GetNotification\GetNotificationQuery;
use App\Application\Notification\Queries\ListNotifications\ListNotificationsQuery;
use App\Domain\Notification\Notification;

interface INotificationServicePort
{
    public function getNotification(GetNotificationQuery $query): Notification;

    /**
     * @return array<int, Notification>
     */
    public function listNotifications(ListNotificationsQuery $query): array;
}
