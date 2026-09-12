<?php

use App\Infrastructure\Customer\Providers\CustomerServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CustomerServiceProvider::class,
];
