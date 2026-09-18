<?php

namespace App\Infrastructure\User\Providers;

use App\Application\User\Ports\Inbound\IUserServicePort;
use App\Application\User\Ports\Outbound\IPasswordHasherPort;
use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Application\User\UserService;
use App\Infrastructure\User\Adapters\Persistence\Repositories\EloquentUserRepository;
use App\Infrastructure\User\Adapters\Security\LaravelPasswordHasher;
use App\Infrastructure\User\Providers\V1\UserServiceProvider as UserServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IUserRepositoryPort::class, EloquentUserRepository::class);
        $this->app->bind(IPasswordHasherPort::class, LaravelPasswordHasher::class);
        $this->app->bind(IUserServicePort::class, UserService::class);

        $this->app->register(UserServiceProviderV1::class);
    }
}
