<?php

namespace App\Infrastructure\Payment\Providers;

use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;
use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Infrastructure\Payment\Adapters\PaymentGateway\Fake\FakePaymentGateway;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Infrastructure\Payment\Providers\V1\PaymentServiceProvider as PaymentServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IPaymentRepositoryPort::class, EloquentPaymentRepository::class);

        // No Stripe adapter exists yet — Fake is bound unconditionally
        // until it does. Once it does, this branches by environment the
        // same way IEventPublisherPort does in Shared\AppServiceProvider.
        $this->app->bind(IPaymentGatewayPort::class, FakePaymentGateway::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(PaymentServiceProviderV1::class);
    }
}
