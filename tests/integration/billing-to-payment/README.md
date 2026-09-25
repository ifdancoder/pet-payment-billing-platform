# Service integration: Billing → Payment

*[Русская версия](README.ru.md)*

The second service integration test in the platform's test pyramid
(see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Exercises billing-api consuming `subscription.created.v1` and opening
an Invoice, then publishing `invoice.created.v1` through a real Outbox
and a real RabbitMQ, payment-api consuming it and auto-processing a
Payment. Not an E2E test: no Identity, Customer, Catalog, Subscription
or Notification.

## Why no subscription-service

Billing only ever creates an Invoice by consuming
`subscription.created.v1` — there's no direct HTTP endpoint for it.
Rather than pull subscription-service (and, transitively, customer/
catalog-service) into this stack just to produce that one event, the
test publishes it directly onto the exchange itself (see
[`tests/Support/EventPublisher.php`](tests/Support/EventPublisher.php)),
in exactly the wire format `RabbitMqEventPublisher` produces.
Subscription actually publishing this event correctly is already
covered by
[`tests/integration/subscription-to-billing/`](../subscription-to-billing/) —
duplicating that coverage here would just make this test slower and
its failures harder to localize.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one logical database per app service |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes directly |
| `billing-api` | exposes `GET /invoices` for the test's first assertion |
| `billing-consumer` | the real `billing-events:consume` loop that turns the published event into an Invoice |
| `billing-outbox` | the real Outbox relay that publishes `invoice.created.v1` |
| `payment-api` | exposes `GET /payments` for the test's second assertion |
| `payment-consumer` | the real `invoice-created:consume` loop that auto-creates and processes a Payment |

## Running it

```bash
cd tests/integration/billing-to-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## A duplicated race fix, worth knowing about

`EventPublisher` declares and binds `billing.events.v1` (the exact same
queue name and routing key `ConsumeBillingEventsCommand` uses) before
publishing. Without that, publishing before `billing-consumer`'s own
first loop iteration has run would silently drop the message — a topic
exchange has nowhere to route it yet. Declaring the same queue from two
places is safe (idempotent), and was worth doing explicitly rather than
padding the test with a fixed startup delay.
