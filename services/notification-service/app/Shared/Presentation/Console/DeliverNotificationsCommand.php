<?php

namespace App\Shared\Presentation\Console;

use App\Application\Notification\Commands\DeliverNotifications\DeliverNotificationsHandler;
use Illuminate\Console\Command;

final class DeliverNotificationsCommand extends Command
{
    protected $signature = 'notifications:deliver';

    protected $description = 'Deliver every Pending notification.';

    public function handle(DeliverNotificationsHandler $handler): int
    {
        $delivered = $handler->handle();

        $this->info("Delivered {$delivered} notification(s).");

        return self::SUCCESS;
    }
}
