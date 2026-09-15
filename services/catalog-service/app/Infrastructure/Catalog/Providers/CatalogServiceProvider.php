<?php

namespace App\Infrastructure\Catalog\Providers;

use App\Application\Catalog\CatalogService;
use App\Application\Catalog\Ports\Inbound\ICatalogServicePort;
use App\Application\Catalog\Ports\Outbound\ICatalogQueryPort;
use App\Infrastructure\Catalog\Adapters\Persistence\Queries\EloquentCatalogQuery;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ICatalogQueryPort::class, EloquentCatalogQuery::class);
        $this->app->bind(ICatalogServicePort::class, CatalogService::class);
    }
}
