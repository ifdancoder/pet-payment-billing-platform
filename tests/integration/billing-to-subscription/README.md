# Service integration: Billing → Subscription

*[Русская версия](README.ru.md)*

The fourth service integration test in the platform's test pyramid
(see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Exercises subscription-consumer's `invoice.paid.v1` and
`invoice.payment_failed.v1` handling — including the guard conditions
their handlers encode, not just the happy path (see
`HandleInvoicePaidHandler`'s and
`HandleInvoicePaymentFailedHandler`'s own docblocks in
subscription-service). Not an E2E test: no Identity, no Payment, no
Notification, and Billing itself isn't in this stack either (see
below). Customer and Catalog *are* — not as the boundary under test,
but because creating a Subscription at all requires them.

## Why this one's different from the others

Unlike Invoice or Payment, Subscription has a real `POST /subscriptions`
HTTP endpoint — no direct-publish trick is needed to seed one, just a
real request through the real create flow. But that flow calls
customer-service and catalog-service synchronously
(`HttpCustomerGateway`, `HttpCatalogGateway`), so both are in this
stack even though neither is the boundary this test exists to verify
— the same reasoning `subscription-to-billing/` already established
for including them.

billing-service is *not* in this stack: it publishing
`invoice.paid.v1`/`invoice.payment_failed.v1` correctly is already
covered by
[`tests/integration/payment-to-billing/`](../payment-to-billing/), so
this test publishes them directly instead (see
[`BillingToSubscriptionTest.php`](tests/BillingToSubscriptionTest.php)),
standing in for Billing's own Outbox.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one logical database per app service |
| `rabbitmq` | the real broker — AMQP port published to the host, since the test publishes directly |
| `customer-service` | subscription-api's synchronous dependency (must find the customer) |
| `catalog-service` | subscription-api's synchronous dependency (must find the price) |
| `subscription-api` | the real create flow, plus `GET /subscriptions/{id}` for the test's assertions |
| `subscription-consumer` | the real `subscription-events:consume` loop that turns the two published events into state transitions |

## Running it

```bash
cd tests/integration/billing-to-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Two tests, not one — including a guard condition

`HandleInvoicePaymentFailedHandler` only transitions `Active` →
`PastDue`; a Pending subscription's very first failed payment (before
it's ever been activated) stays Pending. Asserting that guard for real
needs a subscription that's *actually* Active, not just any
pre-existing one — so the second test first publishes `invoice.paid.v1`
and waits for `active` before publishing `invoice.payment_failed.v1`
and asserting `past_due`. Testing only the happy path here would have
missed whether that guard condition — not just the transition itself —
actually holds when driven by a real, asynchronous, redeliverable
event rather than a direct method call in an Application-layer test.
