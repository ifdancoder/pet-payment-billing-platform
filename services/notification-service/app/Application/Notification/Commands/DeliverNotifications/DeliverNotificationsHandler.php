<?php

namespace App\Application\Notification\Commands\DeliverNotifications;

use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationCommand;
use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationHandler;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;

/**
 * Drains every Pending notification and delivers it. Deliberately its
 * own worker rather than being chained onto CreateNotificationHandler —
 * notification-ingest (consuming payment.succeeded.v1) and
 * notification-delivery (sending email) are independently scalable
 * Kubernetes workloads, unlike payment-service's tighter
 * Create+Process chaining within one consumer invocation.
 */
final class DeliverNotificationsHandler
{
    public function __construct(
        private readonly INotificationRepositoryPort $repository,
        private readonly DeliverNotificationHandler $deliverHandler,
    ) {}

    public function handle(): int
    {
        $delivered = 0;

        foreach ($this->repository->pending() as $notification) {
            $this->deliverHandler->handle(new DeliverNotificationCommand(
                $notification->id()->toString(),
                $notification->merchantId()->toString(),
            ));
            $delivered++;
        }

        return $delivered;
    }
}
