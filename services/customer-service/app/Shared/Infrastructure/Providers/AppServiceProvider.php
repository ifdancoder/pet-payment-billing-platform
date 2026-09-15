<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Infrastructure\Messaging\LogEventPublisher;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use App\Shared\Infrastructure\Persistence\Eloquent\Outbox\EloquentOutbox;
use App\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use App\Shared\Presentation\Console\PublishOutboxMessagesCommand;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ITransactionManagerPort::class, LaravelTransactionManager::class);
        $this->app->bind(IOutboxPort::class, EloquentOutbox::class);

        if ($this->app->environment('testing')) {
            $this->app->bind(IEventPublisherPort::class, LogEventPublisher::class);

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
            $channel->exchange_declare(RabbitMqEventPublisher::EXCHANGE, 'topic', false, true, false);

            return $channel;
        });

        $this->app->bind(IEventPublisherPort::class, RabbitMqEventPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishOutboxMessagesCommand::class,
            ]);
        }
    }
}
