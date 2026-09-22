<?php

namespace Tests\Support;

use RuntimeException;

/**
 * A thin wrapper around the `kubectl` CLI, scoped to one namespace.
 * Shells out rather than using a Kubernetes client library — this
 * suite needs a handful of specific commands (rollout restart, replica
 * counts, pod names), not a general-purpose API client, and every
 * other layer in tests/ already prefers shelling out to the real tool
 * (`docker compose`) over a library wrapping it. See
 * docs/architecture/testing-strategy.md.
 */
final class Kubectl
{
    public function __construct(
        private readonly string $namespace = 'pet-payment-billing-platform',
    ) {}

    /**
     * Always creates a new ReplicaSet, even with no spec change — safe
     * to call on every test run without needing an actual image/config
     * diff to trigger a real rollout.
     */
    public function rolloutRestart(string $deployment): void
    {
        $this->run("rollout restart deployment/{$deployment}");
    }

    /**
     * @return array{replicas: int, updatedReplicas: int, readyReplicas: int}
     */
    public function replicaStatus(string $deployment): array
    {
        $json = $this->run("get deployment/{$deployment} -o json");
        $status = json_decode($json, true, 512, JSON_THROW_ON_ERROR)['status'] ?? [];

        return [
            'replicas' => $status['replicas'] ?? 0,
            'updatedReplicas' => $status['updatedReplicas'] ?? 0,
            'readyReplicas' => $status['readyReplicas'] ?? 0,
        ];
    }

    /**
     * @return list<string>
     */
    public function podNames(string $labelSelector): array
    {
        $output = $this->run("get pods -l ".escapeshellarg($labelSelector)." -o jsonpath={.items[*].metadata.name}");

        if ($output === '') {
            return [];
        }

        $names = explode(' ', $output);
        sort($names);

        return $names;
    }

    private function run(string $args): string
    {
        $command = 'kubectl -n '.escapeshellarg($this->namespace)." {$args} 2>&1";

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("`kubectl {$args}` failed (exit {$exitCode}):\n".implode("\n", $output));
        }

        return implode("\n", $output);
    }
}
