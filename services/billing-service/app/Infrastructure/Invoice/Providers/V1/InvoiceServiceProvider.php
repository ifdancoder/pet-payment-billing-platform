<?php

namespace App\Infrastructure\Invoice\Providers\V1;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../../../Presentation/Http/V1/Routes/invoice.php');
            });
    }
}
