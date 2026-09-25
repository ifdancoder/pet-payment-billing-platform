<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ITransactionManagerPort::class, LaravelTransactionManager::class);
    }

    public function boot(): void {}
}
