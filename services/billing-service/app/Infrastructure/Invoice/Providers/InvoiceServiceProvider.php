<?php

namespace App\Infrastructure\Invoice\Providers;

use App\Application\Invoice\InvoiceService;
use App\Application\Invoice\Ports\Inbound\IInvoiceServicePort;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IInvoiceRepositoryPort::class, EloquentInvoiceRepository::class);
        $this->app->bind(IInvoiceServicePort::class, InvoiceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../Presentation/Invoice/Adapters/Inbound/Http/Routes/api.php');
            });
    }
}
