<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Infrastructure\Persistence\Eloquent\Inbox\EloquentInbox;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * No Outbox or event publisher here, unlike the other services: this
     * service is a pure consumer today and publishes nothing downstream —
     * nothing subscribes to "a notification was created/sent" yet, so
     * there's no pattern to add for symmetry alone.
     */
    public function register(): void
    {
        $this->app->bind(ITransactionManagerPort::class, LaravelTransactionManager::class);
        $this->app->bind(IInboxPort::class, EloquentInbox::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
