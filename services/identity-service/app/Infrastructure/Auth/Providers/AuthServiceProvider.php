<?php

namespace App\Infrastructure\Auth\Providers;

use App\Application\Auth\AuthService;
use App\Application\Auth\Ports\Inbound\IAuthServicePort;
use App\Application\Auth\Ports\Outbound\IAccessTokenIssuerPort;
use App\Application\Auth\Ports\Outbound\IRefreshTokenRepositoryPort;
use App\Infrastructure\Auth\Adapters\Persistence\Repositories\EloquentRefreshTokenRepository;
use App\Infrastructure\Auth\Adapters\Security\Ed25519AccessTokenIssuer;
use App\Infrastructure\Auth\Providers\V1\AuthServiceProvider as AuthServiceProviderV1;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IAuthServicePort::class, AuthService::class);
        $this->app->bind(IRefreshTokenRepositoryPort::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(IAccessTokenIssuerPort::class, Ed25519AccessTokenIssuer::class);

        $this->app->register(AuthServiceProviderV1::class);
    }
}
