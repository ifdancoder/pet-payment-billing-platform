<?php

namespace App\Infrastructure\Price\Providers;

use App\Application\Price\Ports\Inbound\IPriceServicePort;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Price\PriceService;
use App\Infrastructure\Price\Adapters\Persistence\Repositories\EloquentPriceRepository;
use App\Infrastructure\Price\Providers\V1\PriceServiceProvider as PriceServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class PriceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IPriceRepositoryPort::class, EloquentPriceRepository::class);
        $this->app->bind(IPriceServicePort::class, PriceService::class);

        $this->app->register(PriceServiceProviderV1::class);
    }
}
