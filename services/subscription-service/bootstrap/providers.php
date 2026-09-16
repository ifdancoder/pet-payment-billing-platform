<?php

use App\Infrastructure\Subscription\Providers\SubscriptionServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    SubscriptionServiceProvider::class,
];
