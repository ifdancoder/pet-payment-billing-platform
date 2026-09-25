<?php

namespace Platform\Auth\Laravel;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Platform\Auth\Ed25519AccessTokenCodec;

final class AuthContractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/auth_tokens.php', 'auth_tokens');

        $this->app->singleton(Ed25519AccessTokenCodec::class, fn (): Ed25519AccessTokenCodec => new Ed25519AccessTokenCodec(
            issuer: (string) config('auth_tokens.issuer'),
            audience: (string) config('auth_tokens.audience'),
            publicKeyBase64: (string) config('auth_tokens.public_key'),
            secretKeyBase64: config('auth_tokens.secret_key') === null ? null : (string) config('auth_tokens.secret_key'),
            timeToLiveSeconds: (int) config('auth_tokens.access_ttl_seconds'),
            clockLeewaySeconds: (int) config('auth_tokens.clock_leeway_seconds'),
            keyId: (string) config('auth_tokens.key_id'),
        ));
    }

    public function boot(Router $router): void
    {
        $router->pushMiddlewareToGroup('api', CorrelationIdMiddleware::class);
    }
}
