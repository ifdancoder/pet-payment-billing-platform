# Service integration: Payment → Notification

*[Русская версия](README.ru.md)*

The fifth service integration test in the platform's test pyramid (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
The boundary itself is already exercised for real by the
[`successful-subscription`](../../e2e/successful-subscription/) E2E
scenario, but a focused 2-service test still localizes a break here far
faster than a 16-container E2E run would. Not an E2E test: no Identity,
Catalog, Subscription, Billing or Payment.

## Why customer-service is here, and payment/billing aren't

`PaymentSucceededConsumer` looks the customer up synchronously over
HTTP to get an email address before it can render a receipt — a real
dependency of this boundary, not the boundary itself, so
customer-service is in this stack. payment-service and billing-service
are *not*: Payment publishing `payment.succeeded.v1` correctly is
already covered by
[`tests/integration/billing-to-payment/`](../billing-to-payment/), so
this test publishes it directly instead, standing in for Payment's own
Outbox — same reasoning as every slice before it.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one logical database per app service |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes directly |
| `customer-service` | notification's synchronous dependency (must find the customer's email) |
| `notification-api` | exposes `GET /notifications` for the test's assertions |
| `notification-ingest-consumer` | the real `payment-succeeded:consume` loop — creates a Pending Notification |
| `notification-delivery-worker` | the real `notifications:deliver` loop — a *different* consumer, of the Notification row itself rather than a RabbitMQ event, turns it Sent |

## Running it

```bash
cd tests/integration/payment-to-notification
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Two tests: the happy path, and a guard that has to prove a negative

`PaymentSucceededConsumer`'s own guard: if the customer lookup finds
nothing (deleted customer, or customer-service unreachable), neither
the Inbox nor a Notification gets recorded — a redelivery of the same
event just retries the lookup later, rather than the consumer
recording a "failed" state that would suppress a legitimate retry.

Testing that for real means proving an absence, which `eventually()`
(designed to wait for a condition to become *true*) isn't built for.
The second test publishes `payment.succeeded.v1` for a `customer_id`
that was never created, gives the wrong behavior a real window to show
up (several worker loop iterations via a fixed `sleep(3)`), then
asserts the notification list is still empty. A fixed sleep here isn't
the anti-pattern the rest of this codebase avoids it for — it's the
only honest way to test "this doesn't happen," as opposed to timing out
early on "this hasn't happened *yet*."
