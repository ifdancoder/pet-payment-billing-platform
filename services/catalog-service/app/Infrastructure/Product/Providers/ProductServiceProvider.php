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
    public function register(): void
    {
        $this->app->bind(IProductRepositoryPort::class, EloquentProductRepository::class);
        $this->app->bind(IProductServicePort::class, ProductService::class);

        $this->app->register(ProductServiceProviderV1::class);
    }
}
