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
    public function register(): void
    {
        $this->app->bind(IInvoiceRepositoryPort::class, EloquentInvoiceRepository::class);
        $this->app->bind(IInvoiceServicePort::class, InvoiceService::class);

        $this->app->register(InvoiceServiceProviderV1::class);
    }
}
