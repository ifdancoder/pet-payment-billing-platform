<?php

use App\Infrastructure\Invoice\Providers\InvoiceServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    InvoiceServiceProvider::class,
];
