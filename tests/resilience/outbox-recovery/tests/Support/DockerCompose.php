<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Controls this scenario's own docker-compose.yaml stack from inside
 * the test itself — stopping and restarting billing-outbox mid-test is
 * the entire point of this resilience scenario, not incidental setup,
 * so it belongs in the test run (`composer test`), not in a separate
 * manual step the README would otherwise have to describe.
 *
 * Local to this test for now, not in tests/support/ — the same
 * extraction discipline used for eventually() and AmqpTestClient
 * (copied into each of the first two slices needing them before being
 * shared on the third): if a second resilience test needs the same
 * start/stop control (tests/resilience/rabbitmq-outage/ almost
 * certainly will), copy it there first; extract once a third needs it.
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
