<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Blocks until a specific substring appears in a service's live log
 * stream, or a timeout elapses — the only way this test can time a
 * `docker kill` against the narrow window
 * ConsumeBillingEventsCommand's own comment describes (DB commit
 * done, AMQP ack not yet sent), which
 * CONSUMER_CRASH_TEST_DELAY_MS widens just enough to make observable
 * from outside the container.
 *
 * Local to this test for now — nothing else needs "wait for a log
 * line" yet, unlike DockerCompose (stop/start/kill), which three
 * resilience tests already needed before this one.
 */
final class LogWatcher
{
    public function __construct(private readonly string $projectRoot) {}

    public function waitForLine(string $service, string $needle, float $timeoutSeconds = 15.0): void
    {
        $command = 'cd '.escapeshellarg($this->projectRoot)
            .' && docker compose logs -f --no-color '.escapeshellarg($service);

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (! is_resource($process)) {
            throw new RuntimeException('Failed to start `docker compose logs -f`.');
        }

        stream_set_blocking($pipes[1], false);

        $deadline = microtime(true) + $timeoutSeconds;
        $buffer = '';

        try {
            while (microtime(true) < $deadline) {
                $chunk = fread($pipes[1], 8192);

                if ($chunk !== false && $chunk !== '') {
                    $buffer .= $chunk;

                    if (str_contains($buffer, $needle)) {
                        return;
                    }
                }

                // Polled, not blocked on — 5ms is fine-grained enough
                // to react well within CONSUMER_CRASH_TEST_DELAY_MS's
                // window, without busy-looping the CPU.
                usleep(5_000);
            }

            throw new RuntimeException(
                "Timed out after {$timeoutSeconds}s waiting for \"{$needle}\" in {$service}'s logs."
            );
        } finally {
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_terminate($process);
            proc_close($process);
        }
    }
}
