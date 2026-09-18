<?php

namespace App\Infrastructure\Payment\Providers;

use App\Application\Payment\PaymentService;
use App\Application\Payment\Ports\Inbound\IPaymentServicePort;
use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;
use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Infrastructure\Payment\Adapters\PaymentGateway\Fake\FakePaymentGateway;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Infrastructure\Payment\Providers\V1\PaymentServiceProvider as PaymentServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IPaymentRepositoryPort::class, EloquentPaymentRepository::class);
        $this->app->bind(IPaymentServicePort::class, PaymentService::class);

        $this->app->bind(IPaymentGatewayPort::class, FakePaymentGateway::class);

        $this->app->register(PaymentServiceProviderV1::class);
    }
}
