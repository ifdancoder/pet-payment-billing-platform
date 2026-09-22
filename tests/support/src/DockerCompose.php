<?php

namespace BillingPlatform\TestSupport;

use RuntimeException;

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

    public function kill(string $service, string $signal = 'KILL'): void
    {
        $this->run("kill -s {$signal} {$service}");
    }

    /** @param list<string> $command */
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
