# Event catalog

Every integration event published on `billing.events` today, its
producer, and who actually consumes it — not who's *supposed* to
eventually, unless marked as a known gap. Rebuilt directly from the
`IntegrationEvent` classes, `Consume*Command` classes, and queue
bindings in the code, not from a target design. See
[ADR 0002](../adr/0002-rabbitmq-messaging.md) for the contract this
catalog is governed by.

## Wired end to end

Published, consumed, and covered by Inbox on the consumer side — the
platform's complete async vertical slices today.

| Event | Producer | Consumer(s) |
| --- | --- | --- |
| `subscription.created.v1` | subscription-service | billing-service (`SubscriptionCreatedConsumer` → creates an `Invoice`) |
| `invoice.created.v1` | billing-service | payment-service (`InvoiceCreatedConsumer` → creates + processes a `Payment`) |
| `payment.succeeded.v1` | payment-service | notification-service (`PaymentSucceededConsumer` → sends an email receipt); billing-service (`PaymentSucceededConsumer` → marks the `Invoice` Paid, publishes `invoice.paid.v1`) |
| `payment.failed.v1` | payment-service | billing-service (`PaymentFailedConsumer` → the `Invoice` itself doesn't change, it stays Open awaiting another attempt, but this republishes `invoice.payment_failed.v1`) |
| `invoice.paid.v1` | billing-service | subscription-service (`InvoicePaidConsumer` → activates the `Subscription`, idempotent on every renewal since it's normally already Active by the second payment) |
| `invoice.payment_failed.v1` | billing-service | subscription-service (`InvoicePaymentFailedConsumer` → marks the `Subscription` PastDue, but only when it was Active — a Pending subscription's first-ever failed payment stays Pending, not PastDue) |

This closes the platform's core event chain end to end: Subscription →
Billing → Payment → (Billing, Subscription, Notification).

`payment.succeeded.v1` / `payment.failed.v1` never carry
`subscription_id` — Payment doesn't model subscriptions at all, only
`invoice_id`. Billing is the natural translation hop (its own `Invoice`
already links `invoice_id` ↔ `subscription_id`), which is why it
republishes rather than Subscription consuming these two directly. See
[ADR 0002](../adr/0002-rabbitmq-messaging.md) for the full reasoning.

## Published, no consumer yet

Real gaps against the platform's own intended event chain (see the
root README's system diagram) — not hypothetical events, ones that
already flow onto the exchange with nobody listening.

| Event | Producer | Missing consumer(s) | Why it matters |
| --- | --- | --- | --- |
| `subscription.activated.v1` | subscription-service | none | No known need yet. |
| `subscription.canceled.v1` | subscription-service | none | A cancellation-confirmation notification would consume this; not built. |
| `subscription.past_due.v1` | subscription-service | none | No known need yet. |
| `invoice.voided.v1` | billing-service | none | No known need yet. |
| `customer.created.v1` | customer-service | none | No known need yet. |
| `product.created.v1` | catalog-service | none | **Also never actually reaches RabbitMQ** — catalog-service's `IEventPublisherPort` is bound to `LogEventPublisher` unconditionally, not `RabbitMqEventPublisher`. Its Outbox rows get marked published after only being logged. |
| `product.archived.v1` | catalog-service | none | Same publisher gap as `product.created.v1`. |
| `price.created.v1` | catalog-service | none | Same publisher gap. |
| `price.activated.v1` | catalog-service | none | Same publisher gap. |
| `price.deactivated.v1` | catalog-service | none | Same publisher gap. |

Per the rule of thumb in ADR 0002's naming section — an event with no
consumer probably shouldn't be published yet — most of these are
harmless (cheap to keep publishing, ready the day a consumer shows up).
The five catalog-service events are the exception: they're not really
"published with no consumer," they're **not published at all** despite
the Outbox believing they are. That's a bug, not a design choice, and
it's invisible from inside catalog-service itself — only visible by
checking what's actually bound to `IEventPublisherPort`.

## Envelope

See [ADR 0002](../adr/0002-rabbitmq-messaging.md#envelope) for the
exact header/body split. In short: the JSON body is only the event's
own business payload; `event_id`, `aggregate_type`, `aggregate_id`, and
`occurred_at` live in AMQP headers, and the routing key is the event
type string itself.

## Adding an event to this catalog

When a new `IntegrationEvent` class ships, add a row here in the same
commit. An event that exists in code but not in this catalog is exactly
the kind of silent coupling this document exists to prevent.
