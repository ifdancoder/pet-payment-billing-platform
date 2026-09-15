<?php

use App\Infrastructure\Price\Providers\PriceServiceProvider;
use App\Infrastructure\Product\Providers\ProductServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    ProductServiceProvider::class,
    PriceServiceProvider::class,
];
