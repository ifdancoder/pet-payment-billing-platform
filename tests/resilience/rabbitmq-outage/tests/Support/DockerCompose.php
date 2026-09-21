<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Controls this scenario's own docker-compose.yaml stack from inside
 * the test itself — stopping and restarting rabbitmq mid-test is the
 * entire point of this resilience scenario, not incidental setup, so
 * it belongs in the test run (`composer test`), not in a separate
 * manual step the README would otherwise have to describe.
 *
 * This is its second copy — first written for
 * tests/resilience/outbox-recovery/ (see that file's own history) —
 * so per the extraction discipline used for eventually() and
 * AmqpTestClient (copy for the first two callers, share once a third
 * needs it), this is the point to move it into tests/support/ the next
 * time a resilience test needs start/stop control, rather than copying
 * it a third time.
 */
final class DockerCompose
{
    public static function stop(string $service): void
    {
        self::run("stop {$service}");
    }

    public static function start(string $service): void
    {
        self::run("start {$service}");
    }

    private static function run(string $args): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $command = 'cd '.escapeshellarg($projectRoot).' && docker compose '.$args.' 2>&1';

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "`docker compose {$args}` failed (exit {$exitCode}):\n".implode("\n", $output)
            );
        }
    }
}
