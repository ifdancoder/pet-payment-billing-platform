<?php

namespace App\Infrastructure\Merchant\Providers;

use App\Application\Merchant\MerchantService;
use App\Application\Merchant\Ports\Inbound\IMerchantServicePort;
use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Infrastructure\Merchant\Adapters\Persistence\Repositories\EloquentMerchantRepository;
use App\Infrastructure\Merchant\Providers\V1\MerchantServiceProvider as MerchantServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class MerchantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IMerchantRepositoryPort::class, EloquentMerchantRepository::class);
        $this->app->bind(IMerchantServicePort::class, MerchantService::class);

        $this->app->register(MerchantServiceProviderV1::class);
    }
}
