<?php

namespace App\Infrastructure\Subscription\Providers;

use App\Application\Subscription\Ports\Inbound\ISubscriptionServicePort;
use App\Application\Subscription\Ports\Outbound\ICatalogGatewayPort;
use App\Application\Subscription\Ports\Outbound\ICustomerGatewayPort;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Application\Subscription\SubscriptionService;
use App\Infrastructure\Subscription\Adapters\Gateways\HttpCatalogGateway;
use App\Infrastructure\Subscription\Adapters\Gateways\HttpCustomerGateway;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Infrastructure\Subscription\Providers\V1\SubscriptionServiceProvider as SubscriptionServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ISubscriptionRepositoryPort::class, EloquentSubscriptionRepository::class);
        $this->app->bind(ISubscriptionServicePort::class, SubscriptionService::class);

        $this->app->bind(
            ICustomerGatewayPort::class,
            fn () => new HttpCustomerGateway(config('services.customer_service.base_url')),
        );

        $this->app->bind(
            ICatalogGatewayPort::class,
            fn () => new HttpCatalogGateway(config('services.catalog_service.base_url')),
        );

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(SubscriptionServiceProviderV1::class);
    }
}
