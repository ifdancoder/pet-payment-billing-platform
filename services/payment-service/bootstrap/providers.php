<?php

use App\Infrastructure\Payment\Providers\PaymentServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    PaymentServiceProvider::class,
];
