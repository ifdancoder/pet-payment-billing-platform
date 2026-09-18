<?php

namespace App\Infrastructure\Notification\Providers;

use App\Application\Notification\NotificationService;
use App\Application\Notification\Ports\Inbound\INotificationServicePort;
use App\Application\Notification\Ports\Outbound\ICustomerContactGatewayPort;
use App\Application\Notification\Ports\Outbound\IEmailSenderPort;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Application\Notification\Ports\Outbound\ITemplateRendererPort;
use App\Infrastructure\Notification\Adapters\EmailSender\Fake\FakeEmailSender;
use App\Infrastructure\Notification\Adapters\Gateways\HttpCustomerContactGateway;
use App\Infrastructure\Notification\Adapters\Persistence\Repositories\EloquentNotificationRepository;
use App\Infrastructure\Notification\Adapters\TemplateRenderer\BladeTemplateRenderer;
use App\Infrastructure\Notification\Providers\V1\NotificationServiceProvider as NotificationServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(INotificationRepositoryPort::class, EloquentNotificationRepository::class);
        $this->app->bind(INotificationServicePort::class, NotificationService::class);
        $this->app->bind(ITemplateRendererPort::class, BladeTemplateRenderer::class);

        $this->app->bind(IEmailSenderPort::class, FakeEmailSender::class);

        $this->app->bind(
            ICustomerContactGatewayPort::class,
            fn () => new HttpCustomerContactGateway(config('services.customer_service.base_url')),
        );

        $this->app->register(NotificationServiceProviderV1::class);
    }

    public function boot(): void
    {

        $this->loadViewsFrom(resource_path('notification-templates'), 'notification-templates');
    }
}
