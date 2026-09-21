# Resilience: duplicate delivery

The platform's first resilience test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Not a business scenario like [`tests/e2e/`](../../e2e/) or a boundary
like [`tests/integration/`](../../integration/) — a specific claim
about failure behavior: does Billing's Inbox actually stop a
redelivered `subscription.created.v1` from creating a second Invoice,
or does that guarantee only exist in a docblock.

## Why this, first

At-least-once delivery means RabbitMQ *will* redeliver a message whose
ack it never saw — a consumer crashing after committing its
transaction but before acking, or an unacked message simply timing
out. Every such crash produces the same thing on the wire: the same
`event_id` arriving twice. This test doesn't reproduce the crash itself
(that's `tests/resilience/consumer-crash/`, not built yet) — it
reproduces the one thing every version of that crash has in common,
directly, by publishing the identical `event_id` twice via
[`AmqpTestClient::publish()`](../../support/src/AmqpTestClient.php)'s
`eventId` parameter (added for exactly this test — every other slice
lets it auto-generate one).

Reuses the same `billing-consumer` boundary
[`subscription-to-billing/`](../../integration/subscription-to-billing/)
and [`billing-to-payment/`](../../integration/billing-to-payment/)
already exercise — this isn't testing a new boundary, it's asking a new
question about an existing one.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes the duplicate directly |
| `billing-api` | exposes `GET /invoices` for the test's assertions |
| `billing-consumer` | the real `billing-events:consume` loop — the thing under test |

## Running it

```bash
cd tests/resilience/duplicate-delivery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live: the consumer actually processed both deliveries

Checked in `billing-consumer`'s own logs, not inferred from the test
passing: it logged `Consumed 1 message(s).` twice — RabbitMQ has no
concept of "this is the same event as an earlier one," it delivered
both messages exactly as published. Only one Invoice exists anyway.
That's the Inbox's `recordIfNew()` guard actually holding, not
RabbitMQ quietly doing the deduplication for it.

## Another `sleep()`, and why

Same deliberate exception documented in
[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/):
proving "no second Invoice appears" is proving an absence, which
`eventually()` (built to wait for a condition to become *true*) isn't
for. This gives the wrong behavior a real window — several actual
`billing-consumer` loop iterations via a fixed `sleep(3)` — before
asserting the final state.
