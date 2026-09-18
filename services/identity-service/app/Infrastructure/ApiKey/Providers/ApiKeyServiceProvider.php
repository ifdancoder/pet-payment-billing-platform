<?php

namespace App\Infrastructure\ApiKey\Providers;

use App\Infrastructure\ApiKey\Providers\V1\ApiKeyServiceProvider as ApiKeyServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class ApiKeyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(ApiKeyServiceProviderV1::class);
    }
}
