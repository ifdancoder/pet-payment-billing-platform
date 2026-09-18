<?php

namespace App\Infrastructure\Customer\Providers;

use App\Application\Customer\CustomerService;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Application\Customer\Ports\Outbound\INotificationPort;
use App\Infrastructure\Customer\Adapters\Notification\MailNotificationAdapter;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;
use App\Infrastructure\Customer\Providers\V1\CustomerServiceProvider as CustomerServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ICustomerRepositoryPort::class, EloquentCustomerRepository::class);
        $this->app->bind(INotificationPort::class, MailNotificationAdapter::class);
        $this->app->bind(ICustomerServicePort::class, CustomerService::class);

        $this->app->register(CustomerServiceProviderV1::class);
    }
}
