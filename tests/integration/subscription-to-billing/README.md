# Service integration: Subscription → Billing

The first service integration test in the platform's test pyramid (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Exercises exactly what belongs to this boundary — Subscription
publishing `subscription.created.v1` through a real Outbox and a real
RabbitMQ, Billing consuming it and opening an Invoice — nothing more.
Not an E2E test: no Identity, no Payment, no Notification.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one logical database per app service |
| `rabbitmq` | the real broker, no mocking |
| `customer-service` | subscription-api's synchronous dependency (must find the customer) |
| `catalog-service` | subscription-api's synchronous dependency (must find the price) |
| `subscription-api` | creates the Subscription + an Outbox row |
| `subscription-outbox` | the real Outbox relay, same command as in Kubernetes |
| `billing-api` | exposes `GET /invoices` for the test's assertion |
| `billing-consumer` | the real `billing-events:consume` loop that turns the event into an Invoice |

Each app container runs `php artisan migrate --force` before starting —
fine for a single, disposable instance; the "migrate as a separate Job"
pattern in Kubernetes exists specifically to avoid an N-replica race,
which doesn't apply here.

## Running it

```bash
cd tests/integration/subscription-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Why `eventually()`, not `sleep()`

The 201 response from `POST /subscriptions` only proves the Subscription
and its Outbox row were written — not that Billing has processed
anything yet. That happens on `subscription-outbox`'s and
`billing-consumer`'s own polling loops, entirely out of band. Asserting
immediately is a race; a fixed `sleep(N)` is both slow (always pays the
worst case) and still flaky (a loaded CI box can still lose the race).
[`eventually()`](../../support/src/eventually.php), from the shared
[`tests/support/`](../../support/) package, polls the assertion itself
until it stops throwing or a timeout elapses.

## Extending this scenario

Per the testing strategy doc, the next vertical slices to add (each its
own service integration test, not folded into this one) are Billing →
Payment and Payment → Billing → Subscription, before they get stitched
into a full E2E scenario.
