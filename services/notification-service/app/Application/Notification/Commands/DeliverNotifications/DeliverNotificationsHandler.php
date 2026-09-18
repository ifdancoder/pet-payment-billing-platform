<?php

namespace App\Application\Notification\Commands\DeliverNotifications;

use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationCommand;
use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationHandler;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;

/** Runs provider I/O outside the event consumer. */
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
