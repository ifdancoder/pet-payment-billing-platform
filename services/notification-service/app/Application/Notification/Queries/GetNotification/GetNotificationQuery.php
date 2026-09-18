<?php

namespace App\Application\Notification\Queries\GetNotification;

final class GetNotificationQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
    ) {}
}
