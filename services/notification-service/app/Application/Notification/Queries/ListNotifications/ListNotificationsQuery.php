<?php

namespace App\Application\Notification\Queries\ListNotifications;

final class ListNotificationsQuery
{
    public function __construct(public readonly string $merchantId) {}
}
