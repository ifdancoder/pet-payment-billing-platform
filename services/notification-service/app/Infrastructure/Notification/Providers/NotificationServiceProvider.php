<?php

namespace App\Infrastructure\Notification\Providers;

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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(INotificationRepositoryPort::class, EloquentNotificationRepository::class);
        $this->app->bind(ITemplateRendererPort::class, BladeTemplateRenderer::class);

        // No real provider adapter exists yet — Fake is bound
        // unconditionally until it does. Once it does, this branches by
        // environment the same way payment-service's IPaymentGatewayPort
        // eventually will.
        $this->app->bind(IEmailSenderPort::class, FakeEmailSender::class);

        $this->app->bind(
            ICustomerContactGatewayPort::class,
            fn () => new HttpCustomerContactGateway(config('services.customer_service.base_url')),
        );

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(NotificationServiceProviderV1::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Message bodies, not application UI — kept out of resources/views
        // and its default namespace.
        $this->loadViewsFrom(resource_path('notification-templates'), 'notification-templates');
    }
}
