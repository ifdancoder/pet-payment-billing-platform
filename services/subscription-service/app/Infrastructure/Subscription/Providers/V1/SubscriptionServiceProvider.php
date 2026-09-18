<?php

namespace App\Infrastructure\Subscription\Providers\V1;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register any V1-specific application services.
     *
     * Nothing today — V1 reuses the version-agnostic domain/application
     * bindings registered by the parent SubscriptionServiceProvider. A
     * future version whose Controllers/Resources diverge from V1's would
     * bind its own here instead.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../../Presentation/Http/V1/Routes/subscription.php');
            });
    }
}
