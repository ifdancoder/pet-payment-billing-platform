# Event catalog

Every integration event published on `billing.events` today, its
producer, and who actually consumes it — not who's *supposed* to
eventually, unless marked as a known gap. Rebuilt directly from the
`IntegrationEvent` classes, `Consume*Command` classes, and queue
bindings in the code, not from a target design. See
[ADR 0002](../adr/0002-rabbitmq-messaging.md) for the contract this
catalog is governed by.

## Wired end to end

These three are published, consumed, and covered by Inbox on the
consumer side — the platform's only complete async vertical slices
today.

| Event | Producer | Consumer(s) |
| --- | --- | --- |
| `subscription.created.v1` | subscription-service | billing-service (`SubscriptionCreatedConsumer` → creates an `Invoice`) |
| `invoice.created.v1` | billing-service | payment-service (`InvoiceCreatedConsumer` → creates + processes a `Payment`) |
| `payment.succeeded.v1` | payment-service | notification-service (`PaymentSucceededConsumer` → sends an email receipt) |

## Published, no consumer yet

Real gaps against the platform's own intended event chain (see the
root README's system diagram) — not hypothetical events, ones that
already flow onto the exchange with nobody listening.

| Event | Producer | Missing consumer(s) | Why it matters |
| --- | --- | --- | --- |
| `payment.succeeded.v1` | payment-service | billing-service, subscription-service | Nothing marks an `Invoice` Paid or a `Subscription` Active on a real payment outcome. This is the next concrete implementation slice. |
| `payment.failed.v1` | payment-service | *everyone* — zero consumers exist, not even notification-service | No failure-notification email, no subscription past-due transition on a real failed payment. |
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
