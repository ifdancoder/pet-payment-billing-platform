<?php

namespace App\Infrastructure\Invoice\Providers;

use App\Application\Invoice\InvoiceService;
use App\Application\Invoice\Ports\Inbound\IInvoiceServicePort;
use App\Application\Invoice\Ports\Outbound\IInvoiceRepositoryPort;
use App\Infrastructure\Invoice\Adapters\Persistence\Repositories\EloquentInvoiceRepository;
use App\Infrastructure\Invoice\Providers\V1\InvoiceServiceProvider as InvoiceServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IInvoiceRepositoryPort::class, EloquentInvoiceRepository::class);
        $this->app->bind(IInvoiceServicePort::class, InvoiceService::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(InvoiceServiceProviderV1::class);
    }
}
