<?php

namespace App\Infrastructure\Product\Providers;

use App\Application\Product\Ports\Inbound\IProductServicePort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Application\Product\ProductService;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;
use App\Infrastructure\Product\Providers\V1\ProductServiceProvider as ProductServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IProductRepositoryPort::class, EloquentProductRepository::class);
        $this->app->bind(IProductServicePort::class, ProductService::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(ProductServiceProviderV1::class);
    }
}
