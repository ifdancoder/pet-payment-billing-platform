<?php

function eventually(callable $assertion, float $timeoutSeconds = 10.0, float $intervalSeconds = 0.1): void
{
    $deadline = microtime(true) + $timeoutSeconds;

    while (true) {
        try {
            $assertion();

            return;
        } catch (Throwable $failure) {
            if (microtime(true) >= $deadline) {
                throw $failure;
            }

            usleep((int) ($intervalSeconds * 1_000_000));
        }
    }
}
