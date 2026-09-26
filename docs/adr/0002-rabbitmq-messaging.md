# 2. RabbitMQ messaging contract

*[Русская версия](0002-rabbitmq-messaging.ru.md)*

## Status

Accepted

## Context

Five of the seven services (customer, catalog, subscription, billing,
payment) already have the full Outbox + `IntegrationEvent` stack, four
of them (customer, subscription, billing, payment) actually reach
RabbitMQ through it, and three consumers already exist, forming one
complete, working vertical slice:
`subscription.created.v1` (subscription-service) →
`SubscriptionCreatedConsumer` (billing-service) → `Invoice` created.
Two more consumers exist on the same pattern: `invoice.created.v1` →
payment-service, `payment.succeeded.v1` → notification-service.

All of this was built service-by-service, each one copying the
previous service's `RabbitMqEventPublisher` / `EloquentOutbox` /
`EloquentInbox` / `Consume*Command` shape rather than working from a
written contract — because there wasn't one. That's fine for getting
five services to agree by imitation, but it stops being fine now that
the event chain is about to grow (`payment.succeeded.v1` needs a
Billing consumer and a Subscription consumer, not just a Notification
one) and other people/agents need to be able to add a consumer without
re-deriving the rules from whichever service they happened to read
first.

Auditing what actually exists today (not what any design doc says)
surfaced two real gaps against the platform's own intended event chain,
recorded here so they aren't lost:

- **catalog-service's Outbox never reaches RabbitMQ.** It has the full
  Outbox stack (migration, `EloquentOutbox`, publisher command, all its
  `IntegrationEvent` classes) but `IEventPublisherPort` is bound to
  `LogEventPublisher` unconditionally — no `php-amqplib` dependency, no
  `RabbitMqEventPublisher`, no exchange declaration. Its outbox rows get
  marked `published_at` after being logged, never sent anywhere.
- **`payment.succeeded.v1` / `payment.failed.v1` have no Billing or
  Subscription consumer.** Only notification-service consumes them
  today. Nothing marks an `Invoice` Paid or a `Subscription` Active in
  response to a real payment outcome — the two most important legs of
  the platform's own core event chain don't exist yet. This is the next
  concrete piece of work, not something this ADR fixes.

## Decision

### Reliability rules

These aren't new inventions — they're what the five services that
already do this have been doing, written down so the next one doesn't
have to reverse-engineer it:

- **Delivery semantics: at-least-once.** No exactly-once transport
  exists or is promised. A message can and will be redelivered; every
  consumer has to be safe under that.
- **Exchange: one shared topic exchange, `billing.events`, durable.**
  Not one exchange per event or per service — already true today
  (`RabbitMqEventPublisher::EXCHANGE`, identical across every service
  that publishes), just naming it as the rule rather than an accident
  of copy-paste.
- **Critical publishing goes through the Transactional Outbox.** Never
  a direct `$channel->basic_publish()` from inside a request or
  consumer transaction — the local write and the fact-that-an-event-
  happened are recorded in the same DB transaction, and a separate
  publisher process drains the outbox afterward.
- **Critical consuming goes through the Inbox.** `event_id` is the
  dedup key (`inbox_messages.id` PRIMARY KEY,
  `UniqueConstraintViolationException` → treat as already-processed,
  ACK, do nothing). Inbox write, domain effect, and any outgoing Outbox
  rows happen in one DB transaction — never Inbox-write-then-commit
  followed by a separate transaction for the domain effect, since a
  crash between them would mean "Inbox says processed" while the
  business operation never happened.
- **Business idempotency sits on top of Inbox, not instead of it.** Two
  *different* `event_id`s can represent the same underlying business
  fact (a redelivered event under a new id, two upstream retries of the
  same trigger). Inbox alone won't catch that — a unique constraint on
  the actual business key does (already the pattern:
  `invoices.billing_cycle_id` unique in billing-service,
  `notifications.deduplication_key` unique in notification-service).
- **Retries are bounded.** A failed delivery is republished with an
  incremented `delivery_attempt`; after three attempts it is rejected into
  the service queue's durable `.dlq`. Republishing is confirmed before the
  original delivery is acknowledged, avoiding an infinite `requeue=true`
  loop or a retry-loss window.
- **ACK only after durable processing.** A message is acknowledged
  after the DB transaction that recorded its effects has committed —
  never ack-then-process. If the transaction fails, the message is not
  acked and gets redelivered.
- **No global ordering guarantee.** Two related events can arrive out
  of the order they occurred in (retries, redelivery, two consumers
  racing). The domain's own state machine decides what a late or
  out-of-order event means — message order is never load-bearing.
- **RabbitMQ is a transport, not a system of record.** Each service's
  own PostgreSQL database is the source of truth. Nothing is ever
  reconstructed by replaying RabbitMQ from the beginning — this
  platform does not do Event Sourcing.

### Naming convention

**`<entity>.<event>.v<version>`** — already the convention in every one
of the 14 integration events that exist today (`invoice.created.v1`,
`payment.succeeded.v1`, `subscription.canceled.v1`, ...). The entity is
the aggregate name, not the owning service's name — `invoice.created.v1`
from billing-service, not `billing.invoice.created.v1`; `payment.
succeeded.v1` from payment-service, not `payment.payment.succeeded.v1`.
This ADR keeps it rather than switching to a domain-prefixed form: it's
already fully consistent across every existing event, and the point of
picking one scheme is consistency, not which scheme.

A breaking payload change is a new version (`payment.succeeded.v2`),
never a silent mutation of `.v1`'s shape. `.v1` keeps being published
unchanged until every consumer has migrated to `.v2` (versioning
strategy — dual-publish vs. consumer migration order — gets decided
when the first breaking change actually happens, not speculatively
now).

### Envelope

What's actually on the wire today, confirmed against
`RabbitMqEventPublisher::publish()` (byte-for-byte identical in every
service that has one):

- **AMQP message body**: only the event's own `payload()` — e.g. for
  `payment.succeeded.v1`, just `payment_id, invoice_id, merchant_id,
  customer_id, amount_minor_units, currency, paid_at`. No envelope
  wrapper in the body today.
- **AMQP `application_headers`**: `event_id`, `aggregate_type`,
  `aggregate_id`, `occurred_at`.
- **AMQP properties**: `content_type: application/json`,
  `delivery_mode: PERSISTENT`.
- **Routing key**: the event type string itself (`payment.succeeded.v1`).

**Target shape** adds three header fields that don't exist on the wire
yet — `correlation_id`, `causation_id`, `producer` — but wiring them in
is Observability-phase work (propagating a correlation id through an
HTTP request → Outbox → RabbitMQ → consumer → its own Outbox chain
needs each service's request layer to generate/forward one first,
which nothing does today). Recorded here as the target so the header
shape doesn't need re-deciding then:

| Header | Today | Target |
| --- | --- | --- |
| `event_id` | ✓ | ✓ |
| `aggregate_type` | ✓ | ✓ |
| `aggregate_id` | ✓ | ✓ |
| `occurred_at` | ✓ | ✓ |
| `correlation_id` | — | added in the Observability phase |
| `causation_id` | — | added in the Observability phase |
| `producer` | — | added in the Observability phase (the publishing service's own name) |

`correlation_id` and `causation_id` stay out of the JSON body
deliberately even once added — they're transport/observability
metadata, not part of the business contract a consumer's payload
parsing depends on. Same reasoning as keeping `content_type` and
`delivery_mode` out of the body today.

### Queue topology

**A queue belongs to a consumer, never to an event.** Today each
consumer happens to have exactly one queue bound to exactly one routing
key (`billing.subscription-created` ← `subscription.created.v1`,
`payment.invoice-created` ← `invoice.created.v1`,
`notification.payment-succeeded` ← `payment.succeeded.v1`) because each
consuming service only consumes one event type so far. That's about to
stop being true: Billing needs to consume both `subscription.created.v1`
*and* `payment.succeeded.v1`/`payment.failed.v1`, and Subscription needs
the latter two as well.

Going forward, a service gets **one queue per capability**, with
multiple bindings, not one queue per event type:

```
billing.events.v1
    ← subscription.created.v1
    ← payment.succeeded.v1
    ← payment.failed.v1

subscription.events.v1
    ← payment.succeeded.v1
    ← payment.failed.v1
```

This is a decision for *new* queues, not a mandate to rename the three
that already exist for no functional reason — they'll naturally
collapse into this shape as Billing and Subscription gain their second
consumer each.

## Consequences

**Easier:**
- A new consumer has a written contract to build against instead of
  reverse-engineering one service's implementation and hoping it's
  representative.
- The event catalog (`docs/architecture/event-catalog.md`) makes
  the real coupling map — and the real gaps in it — visible instead of
  implicit in which consumer classes happen to exist.
- Capability-level queues mean the queue count grows with the number of
  *consuming services*, not the number of *event types* — bounded and
  predictable as the event catalog grows.

**Harder / follow-up work, explicitly not resolved by this ADR:**
- All five publishers use RabbitMQ confirms, and all consuming queues use
  `basic_qos(..., 1, ...)` before a delivery is read.
- `correlation_id`, `causation_id`, `message_id`, timestamps and delivery
  attempts are carried as transport metadata. Backoff queues remain a valid
  future refinement; current retries are immediate and intentionally bounded.

## Related docs

- [`../architecture/event-catalog.md`](../architecture/event-catalog.md)
  for every event this contract governs today, its producer, and its
  actual (not aspirational) consumers.
- [`0001-api-gateway-routing.md`](0001-api-gateway-routing.md) for the
  synchronous side of inter-service communication — this ADR only
  covers the asynchronous side.
