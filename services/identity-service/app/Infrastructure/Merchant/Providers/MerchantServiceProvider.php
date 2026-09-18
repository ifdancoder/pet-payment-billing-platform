<?php

namespace App\Infrastructure\Merchant\Providers;

use App\Infrastructure\Merchant\Providers\V1\MerchantServiceProvider as MerchantServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class MerchantServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(MerchantServiceProviderV1::class);
    }
}
