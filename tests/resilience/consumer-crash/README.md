# Resilience: consumer crash

The fourth and last planned resilience test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Not the same claim
[`duplicate-delivery/`](../duplicate-delivery/) already proved (that
Inbox ignores an exact duplicate `event_id`, however the duplicate
arose) — this proves the *mechanism* that produces that duplicate in
production actually works end to end: a process dying with a message
it already committed for but never acked, RabbitMQ noticing the
connection dropped and redelivering it, and the real, restarted
consumer hitting Inbox's guard for real.

## Why this needed a small, deliberate change to `billing-service`

Every other resilience test controls Docker or the wire from outside
the application — this is the first one that couldn't, and it's worth
being upfront about why. The window between
`CreateInvoiceHandler`'s transaction committing and
`ConsumeBillingEventsCommand` calling `$message->ack()` is normally
microseconds — far too narrow for any external test (watching logs,
issuing a `docker kill`) to land inside reliably. Two small, additive
changes to `ConsumeBillingEventsCommand` make it observable and
controllable:

1. A log line — `"Processed event {event_id}, acking."` — right after
   the handler returns and right before the ack. Independently useful
   on its own for production debugging of a stuck ack, not just a test
   hook.
2. `CONSUMER_CRASH_TEST_DELAY_MS`, an environment variable read once,
   right before the ack: `if ($delayMs = (int) env(...)) { usleep($delayMs * 1000); }`.
   Unset (`0`) in every real environment, including every other
   `docker-compose.yaml` in this repo — only this test's stack sets it
   (to `5000`), widening the window from microseconds to seconds
   specifically so [`LogWatcher`](tests/Support/LogWatcher.php) can
   reliably observe the log line and
   [`DockerCompose::kill()`](../../support/src/DockerCompose.php) can
   land the `SIGKILL` before the ack — not after it, which is what a
   naive "watch the logs, then kill" approach would race and lose
   almost every time without this.

Both changes are additive and off by default; every other test and
every real deployment sees no behavior change at all.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes the seed event directly |
| `billing-api` | exposes `GET /invoices` for the test's assertions |
| `billing-consumer` | the thing under test — killed (`SIGKILL`, no graceful shutdown) and restarted by the test itself, with `CONSUMER_CRASH_TEST_DELAY_MS` set |

## Running it

```bash
cd tests/resilience/consumer-crash
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live: the exact log signature a real crash-and-recover produces

`"Processed event <id>, acking."` appears **twice** in
`billing-consumer`'s logs for the one event this test publishes — once
for the killed attempt (which never reaches the line after it), once
for the redelivered attempt that actually completes — while
`"Consumed 1 message(s)."` (only reachable *after* a successful ack)
appears exactly **once**. `docker compose ps` right after the run
showed the container `Up` for less time than it had existed — a
genuine kill-and-restart, not a no-op. Together, this is the signature
of a message that was really processed twice from RabbitMQ's
perspective and really committed only once from the database's —
proven by an actual crash, not a second manual publish.
