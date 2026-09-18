<?php

use App\Infrastructure\Notification\Providers\NotificationServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    NotificationServiceProvider::class,
];
