<?php

namespace App\Application\Notification\Commands\DeliverNotification;

final class DeliverNotificationCommand
{
    public function __construct(
        public readonly string $notificationId,
        public readonly string $merchantId,
    ) {}
}
