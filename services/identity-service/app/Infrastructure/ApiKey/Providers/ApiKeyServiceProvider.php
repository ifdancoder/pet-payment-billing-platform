<?php

namespace App\Infrastructure\ApiKey\Providers;

use App\Application\ApiKey\Ports\Outbound\IApiKeyRepositoryPort;
use App\Infrastructure\ApiKey\Adapters\Persistence\Repositories\EloquentApiKeyRepository;
use App\Infrastructure\ApiKey\Providers\V1\ApiKeyServiceProvider as ApiKeyServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class ApiKeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IApiKeyRepositoryPort::class, EloquentApiKeyRepository::class);

        $this->app->register(ApiKeyServiceProviderV1::class);
    }
}
