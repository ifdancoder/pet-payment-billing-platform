<?php

namespace App\Infrastructure\Price\Providers;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Infrastructure\Price\Adapters\Persistence\Repositories\EloquentPriceRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PriceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IPriceRepositoryPort::class, EloquentPriceRepository::class);
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
