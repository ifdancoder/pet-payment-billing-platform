<?php

namespace App\Infrastructure\Customer\Providers;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CustomerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ICustomerRepositoryPort::class, EloquentCustomerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../Presentation/Customer/Adapters/Inbound/Http/Routes/api.php');
            });
    }
}
