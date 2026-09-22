# Service integration: Payment → Billing

The third service integration test in the platform's test pyramid (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Goes one hop further than
[`billing-to-payment/`](../billing-to-payment/): that one stops once
Payment succeeds, this one verifies Billing actually consumes
`payment.succeeded.v1`, marks its Invoice Paid, and republishes
`invoice.paid.v1` with `subscription_id` restored — the whole reason
this translation hop exists (Payment's own event never carries it, see
[ADR 0002](../../../docs/adr/0002-rabbitmq-messaging.md)). Not an E2E
test: no Identity, Customer, Catalog, Subscription or Notification.

## Why the seed event, and why not simulate the rest

Marking an Invoice Paid needs one to already exist in billing's own
database — `MarkInvoicePaidHandler` looks it up by ID and throws if
it's missing. There's no HTTP endpoint to fake that from outside. So
this test seeds the one Invoice it needs by publishing
`subscription.created.v1` directly (same reasoning as
`billing-to-payment/`: Subscription's own Outbox is already covered by
[`subscription-to-billing/`](../subscription-to-billing/)), then lets
the *real* chain run the rest of the way — `invoice.created.v1`, the
Payment, and `payment.succeeded.v1` are all produced by real
`billing-outbox`/`payment-consumer`/`payment-outbox` code, not
simulated. Faking `payment.succeeded.v1` directly (skipping Payment
entirely) was the original plan, but it would've required the test to
invent a `payment_id` that no Payment record actually backs, and
wouldn't have exercised the real `invoice_id` correlation at all — this
version proves the whole boundary works with the system generating and
threading its own IDs, not just that Billing's consumer parses a
well-formed message.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one logical database per app service |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes and reads directly |
| `billing-api` | exposes `GET /invoices` for the test's assertions |
| `billing-consumer` | the real `billing-events:consume` loop — creates the Invoice from the seed event, later marks it Paid |
| `billing-outbox` | the real Outbox relay — publishes `invoice.created.v1`, later `invoice.paid.v1` |
| `payment-api` | exposes `GET /payments` for the test's assertion |
| `payment-consumer` | the real `invoice-created:consume` loop — auto-creates and processes a Payment |
| `payment-outbox` | the real Outbox relay — publishes `payment.succeeded.v1` |

## Running it

```bash
cd tests/integration/payment-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Shared test support

This is the third slice, so per
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)'s
extraction rule, it's the first one to use
[`tests/support/`](../../support/) — a small local Composer package
(pulled in via a `path` repository) providing `eventually()` and
`AmqpTestClient`, a generic publish/bind/read helper for the
`billing.events` exchange — instead of copying `eventually.php` a
third time. `subscription-to-billing/` and `billing-to-payment/` had
their own local copies at the time; both have since been migrated onto
this shared package too.

`AmqpTestClient` is what makes the seed-event and
republish-verification parts of this test possible: it declares/binds
`billing.events.v1` before publishing to it (the same race-avoidance
`billing-to-payment/`'s `EventPublisher` did, generalized), and it can
bind a private, throwaway queue to any routing key
(`bindTestQueue('invoice.paid.v1')`) to assert a service actually
republished something — not just that its own HTTP state changed.
