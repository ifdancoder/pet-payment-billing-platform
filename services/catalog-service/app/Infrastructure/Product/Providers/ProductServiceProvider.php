<?php

namespace App\Infrastructure\Product\Providers;

use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IProductRepositoryPort::class, EloquentProductRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../Presentation/Product/Adapters/Inbound/Http/Routes/api.php');
            });
    }
}
