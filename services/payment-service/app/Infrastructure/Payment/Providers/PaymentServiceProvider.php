<?php

namespace App\Infrastructure\Payment\Providers;

use App\Infrastructure\Payment\Providers\V1\PaymentServiceProvider as PaymentServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(PaymentServiceProviderV1::class);
    }
}
