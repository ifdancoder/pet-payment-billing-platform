# Resilience: outbox recovery

The second resilience test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Not a business scenario or a boundary — a specific claim about failure
behavior: does `billing-outbox` actually catch up on rows it missed
while it was stopped, rather than losing them or needing the original
write replayed. That's the second half of what the Transactional
Outbox pattern promises — the first half (the domain write and its
Outbox row commit together, synchronously) is implicit in every other
test that uses one; this is the only one that actually stops the relay
to check the rest of the promise.

## Why the test controls Docker itself

Stopping and restarting `billing-outbox` mid-scenario *is* the test,
not setup for it — so it happens inside `composer test` via
[`tests/Support/DockerCompose.php`](tests/Support/DockerCompose.php)
(a thin wrapper around `docker compose stop/start`), not as a manual
step this README would otherwise have to describe. Local to this test
for now, not in [`tests/support/`](../../support/) — same extraction
discipline as `eventually()`: copy for the first two resilience tests
that need start/stop control (`rabbitmq-outage/` almost certainly
will), share on the third.

## What's actually proven, and why the ordering matters

`billing-outbox` is stopped *before* anything is created — the Outbox
row this test is about has to be written while the relay is provably
not running, not race a relay that just hasn't reached it yet.
`billing-consumer` doesn't need `billing-outbox` at all to do its own
job: `CreateInvoiceHandler` commits the Invoice and its Outbox row in
one transaction, entirely independent of whether anything is currently
relaying older rows — this is a real, useful property of the pattern,
demonstrated in passing by this test's first assertion succeeding at
all. Once `billing-outbox` starts back up (the *same* container,
restarted — not a fresh replacement, and not the original transaction
replayed), `eventually()` confirms the row it missed reaches the wire.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes the seed event and reads the recovery directly |
| `billing-api` | exposes `GET /invoices` for the test's assertion |
| `billing-consumer` | the real `billing-events:consume` loop — proves it works with the relay down |
| `billing-outbox` | the real Outbox relay — the thing under test, stopped and restarted by the test itself |

## Running it

```bash
cd tests/resilience/outbox-recovery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live: a real stop, not a simulated one

`docker compose ps` right after the test run showed `billing-outbox`
`Up 6 seconds` on a container `Created 35 seconds` earlier — a genuine
stop-and-restart cycle, not a no-op. A container that's stopped can't
log at all, so every `Published 0 outbox message(s).` line in its logs
is from before the stop or after the restart; exactly one
`Published 1 outbox message(s).` appears among them — the row it had
missed, found once it was running again.
