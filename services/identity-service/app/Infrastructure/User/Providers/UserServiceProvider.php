<?php

namespace App\Infrastructure\User\Providers;

use App\Application\User\Ports\Outbound\IPasswordHasherPort;
use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Infrastructure\User\Adapters\Persistence\Repositories\EloquentUserRepository;
use App\Infrastructure\User\Adapters\Security\LaravelPasswordHasher;
use App\Infrastructure\User\Providers\V1\UserServiceProvider as UserServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IUserRepositoryPort::class, EloquentUserRepository::class);
        $this->app->bind(IPasswordHasherPort::class, LaravelPasswordHasher::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(UserServiceProviderV1::class);
    }
}
