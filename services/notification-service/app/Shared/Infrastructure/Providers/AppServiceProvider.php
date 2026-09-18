<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Infrastructure\Persistence\Eloquent\Inbox\EloquentInbox;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use App\Shared\Presentation\Console\ConsumePaymentSucceededCommand;
use App\Shared\Presentation\Console\DeliverNotificationsCommand;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ITransactionManagerPort::class, LaravelTransactionManager::class);
        $this->app->bind(IInboxPort::class, EloquentInbox::class);

        if ($this->app->environment('testing')) {
            return;
        }

        $this->app->singleton(AMQPChannel::class, function () {
            $connection = new AMQPStreamConnection(
                config('services.rabbitmq.host'),
                config('services.rabbitmq.port'),
                config('services.rabbitmq.user'),
                config('services.rabbitmq.password'),
            );

            $channel = $connection->channel();
            $channel->exchange_declare('billing.events', 'topic', false, true, false);

            return $channel;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsumePaymentSucceededCommand::class,
                DeliverNotificationsCommand::class,
            ]);
        }
    }
}
