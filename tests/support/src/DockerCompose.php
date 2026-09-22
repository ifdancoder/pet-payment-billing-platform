<?php

namespace BillingPlatform\TestSupport;

use RuntimeException;

/**
 * Controls a resilience test's own docker-compose.yaml stack from
 * inside the test itself — stopping, killing or restarting a service
 * mid-test *is* the scenario for every test that uses this, not
 * incidental setup, so it belongs in the test run (`composer test`),
 * not in a separate manual step each README would otherwise have to
 * describe.
 *
 * Extracted here on its third use (first written for
 * tests/resilience/outbox-recovery/, copied for
 * tests/resilience/rabbitmq-outage/) — the same copy-first,
 * share-on-third-use discipline as eventually() and AmqpTestClient.
 * Instantiable rather than static, unlike those two: it needs to know
 * which project's docker-compose.yaml to run against, and there's one
 * of those per test, not one shared connection config.
 */
final class DockerCompose
{
    public function __construct(private readonly string $projectRoot) {}

    public function stop(string $service): void
    {
        $this->run("stop {$service}");
    }

    public function start(string $service): void
    {
        $this->run("start {$service}");
    }

    /**
     * A hard kill (SIGKILL by default) — the container's own PID 1
     * (and everything under it) dies immediately with no chance to
     * run a shutdown handler, unlike `stop` (SIGTERM, graceful).
     * That's the point for a crash test: a real crash doesn't wait for
     * `trap ... TERM INT` to finish an in-flight message first.
     */
    public function kill(string $service, string $signal = 'KILL'): void
    {
        $this->run("kill -s {$signal} {$service}");
    }

    /**
     * Runs an operational command in an already-running service without
     * going around its container boundary (used by scheduler smoke tests).
     *
     * @param  list<string>  $command
     */
    public function exec(string $service, array $command): string
    {
        $args = 'exec -T '.escapeshellarg($service).' '.implode(' ', array_map(escapeshellarg(...), $command));

        return $this->run($args);
    }

    private function run(string $args): string
    {
        $command = 'cd '.escapeshellarg($this->projectRoot).' && docker compose '.$args.' 2>&1';

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "`docker compose {$args}` failed (exit {$exitCode}):\n".implode("\n", $output)
            );
        }

        return implode("\n", $output);
    }
}
