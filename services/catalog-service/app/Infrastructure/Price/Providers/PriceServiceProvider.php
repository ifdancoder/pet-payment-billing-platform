<?php

namespace App\Infrastructure\Price\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PriceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
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
            ->prefix('api')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../Presentation/Price/Adapters/Inbound/Http/Routes/api.php');
            });
    }
}
