<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Infrastructure\Messaging\LogEventPublisher;
use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\EloquentOutbox;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ITransactionManagerPort::class, LaravelTransactionManager::class);
        $this->app->bind(IOutboxPort::class, EloquentOutbox::class);
        $this->app->bind(IEventPublisherPort::class, LogEventPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
