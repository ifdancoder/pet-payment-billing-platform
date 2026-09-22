<?php

namespace App\Shared\Presentation\Console;

use App\Application\Subscription\Commands\RenewSubscriptions\RenewSubscriptionsHandler;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Throwable;

final class RenewSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:renew {--as-of= : Process cycles due at this ISO-8601 instant} {--limit=100 : Maximum subscriptions to claim}';

    protected $description = 'Emit one renewal billing event for every Active subscription whose current period has ended.';

    public function handle(RenewSubscriptionsHandler $handler): int
    {
        try {
            $asOf = new DateTimeImmutable($this->option('as-of') ?: 'now');
        } catch (Throwable) {
            $this->error('--as-of must be a valid date/time.');

            return self::FAILURE;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000],
        ]);

        if ($limit === false) {
            $this->error('--limit must be an integer between 1 and 1000.');

            return self::FAILURE;
        }

        $renewed = $handler->handle($asOf, $limit);
        $this->info("Queued {$renewed} subscription renewal(s).");

        return self::SUCCESS;
    }
}
