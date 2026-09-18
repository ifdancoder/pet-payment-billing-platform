<?php

namespace App\Infrastructure\Notification\Providers;

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Infrastructure\Notification\Adapters\Persistence\Repositories\EloquentNotificationRepository;
use App\Infrastructure\Notification\Providers\V1\NotificationServiceProvider as NotificationServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(INotificationRepositoryPort::class, EloquentNotificationRepository::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(NotificationServiceProviderV1::class);
    }
}
