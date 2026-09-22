<?php

namespace Tests\Support;

use RuntimeException;

final class Kubectl
{
    public function __construct(
        private readonly string $namespace = 'pet-payment-billing-platform',
    ) {}

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
        $output = $this->run('get pods -l '.escapeshellarg($labelSelector).' -o jsonpath={.items[*].metadata.name}');

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
