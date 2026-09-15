<?php

namespace App\Shared\Presentation\Console;

use App\Shared\Application\Outbox\PublishOutboxMessagesHandler;
use Illuminate\Console\Command;

final class PublishOutboxMessagesCommand extends Command
{
    protected $signature = 'outbox:publish';

    protected $description = 'Publish every unpublished outbox message and mark it published.';

    public function handle(PublishOutboxMessagesHandler $handler): int
    {
        $published = $handler->handle();

        $this->info("Published {$published} outbox message(s).");

        return self::SUCCESS;
    }
}
