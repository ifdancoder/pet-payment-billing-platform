<?php

/**
 * Polls $assertion until it stops throwing or $timeoutSeconds elapses,
 * then rethrows its last failure. Async flows (an HTTP call that
 * triggers a RabbitMQ publish, consumed by a separate process) can't be
 * asserted on immediately, and a fixed `sleep()` is both slow (always
 * waits the worst case) and flaky (still races on a slow CI box) — see
 * docs/architecture/testing-strategy.md, "Asynchronous assertions".
 */
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
