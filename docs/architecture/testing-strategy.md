# Testing strategy

The living version of [ADR 0004](../adr/0004-testing-strategy.md)'s
decision — what's actually built today, and what's next. Rebuilt
against the code and the `tests/` tree, not a target design; update
this file as slices get added, the same way
[`event-catalog.md`](event-catalog.md) tracks the messaging chain.

## The layers, in one table

| Layer | Verifies | Real DB | Real RabbitMQ | Other services | Lives in |
| --- | --- | --- | --- | --- | --- |
| Unit | a Domain entity/VO | no | no | no | `services/*/tests/Unit/` |
| Application | a Handler, against fakes | no | no | no | `services/*/tests/Integration/Application/` |
| Integration | one adapter | yes | sometimes | no | `services/*/tests/Integration/{Gateways,Messaging,Persistence,Transaction}/` |
| (Laravel Feature) | one service's own HTTP/console entry point, in-process | yes (sqlite) | no (`Http::fake()`) | faked | `services/*/tests/Feature/` |
| Component | one whole service, live process | yes | yes | stub server | not built yet |
| Contract | one event's schema | no | no | no | not built yet |
| Service integration | 2-3 real services + broker | yes | yes | yes (2-3) | `tests/integration/<slice>/` |
| E2E | a full business flow | yes | yes | yes (all) | `tests/e2e/<scenario>/` |
| Resilience | one failure mode | yes | yes | yes | `tests/resilience/<scenario>/` |
| Architecture | dependency-direction rules | no | no | no | `services/*/tests/Architecture/` |

## Status

### Service integration (`tests/integration/`)

| Slice | Status |
| --- | --- |
| `subscription-to-billing` | **Done.** `POST /subscriptions` → real Outbox → real RabbitMQ → real `billing-events:consume` → Invoice Open. See its own [README](../../tests/integration/subscription-to-billing/README.md). |
| `billing-to-payment` | **Done.** Billing consumes a directly-published `subscription.created.v1` → Invoice Open → real Outbox → real RabbitMQ → real `invoice-created:consume` → Payment succeeded. See its own [README](../../tests/integration/billing-to-payment/README.md). |
| `payment-to-billing` | **Done.** A directly-published `subscription.created.v1` seeds an Invoice, then the *real* chain runs the rest of the way (real `billing-outbox`, real `payment-consumer`, real `payment-outbox`) to a real `payment.succeeded.v1` → Billing marks the Invoice Paid and republishes `invoice.paid.v1` (verified on the wire, not just via HTTP). See its own [README](../../tests/integration/payment-to-billing/README.md). |
| `billing-to-subscription` | Not built. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription activates/marks PastDue. |
| `payment-to-notification` | Not built. `payment.succeeded.v1` → Notification delivers a receipt. |

### Everything else in the pyramid

| Layer | Status |
| --- | --- |
| Component | Not built. Would live per-service, e.g. `services/subscription-service/tests/Component/`. |
| Contract | Not built. One producer-side test per event in the [event catalog](event-catalog.md), one consumer-side test per service that consumes it. |
| E2E (`tests/e2e/`) | Not built. Needs `billing-to-subscription` wired first — see "Building the next slice" below. |
| Resilience (`tests/resilience/`) | Not built. |
| `kind`-based platform smoke tests | Not built. Separate from all of the above — see ADR 0004, "Docker Compose for business tests, Kubernetes for platform tests." |

## Building the next slice

Don't jump straight to the full E2E. Each service integration slice
gets built, proven, and merged on its own, the same way the RabbitMQ
event chain itself was built one vertical slice at a time (see the
[event catalog](event-catalog.md)'s own history).

`billing-to-payment` surfaced a real constraint worth carrying forward:
Billing has no direct "create an Invoice" HTTP endpoint — it only ever
creates one by consuming `subscription.created.v1`. Rather than pull a
third service into a "2-service" test just to produce that event, the
test publishes it directly onto the exchange, in the exact wire format
`RabbitMqEventPublisher` produces (see
[`tests/integration/billing-to-payment/tests/Support/EventPublisher.php`](../../tests/integration/billing-to-payment/tests/Support/EventPublisher.php)).
The same pattern generalizes: whichever service integration test comes
next, check first whether the upstream event actually needs a whole
extra service to produce, or whether publishing it directly (like a
Contract test's producer side would) keeps the slice genuinely 2-3
services instead of creeping toward a full chain. It also declares and
binds the target queue itself before publishing — publishing before
the real consumer's own first loop iteration has bound its queue
silently drops the message on a topic exchange, a race worth avoiding
explicitly rather than padding the test with a startup delay.

`payment-to-billing` refined that plan once it hit its own constraint:
marking an Invoice Paid needs one to already exist in billing's own
database (`MarkInvoicePaidHandler` looks it up by ID and throws if it's
missing), so faking `payment.succeeded.v1` directly — the original
plan — would've meant inventing a `payment_id` and `invoice_id` with
nothing real behind them, proving only that the consumer can parse a
well-formed message, not that the boundary works with real,
system-generated, correlated IDs. What it does instead: seed one
Invoice with a directly-published `subscription.created.v1` (same
pattern as `billing-to-payment`), then let the *real* chain run the
rest of the way — `invoice.created.v1`, the Payment, and
`payment.succeeded.v1` are all produced by real `billing-outbox`/
`payment-consumer`/`payment-outbox` code. Direct-publishing is for
seeding state a test has no other way to reach, not a shortcut around
exercising a service's own real publish path when that path is exactly
what's under test.

This is also where `eventually()` and the publish/bind mechanics moved
into [`tests/support/`](../../tests/support/), a shared local Composer
package — the third slice, exactly when the earlier version of this
doc said to extract it (see "Asynchronous assertions" below). Its
`AmqpTestClient` generalizes both `billing-to-payment`'s
`EventPublisher` (publish + declare/bind before publishing) and adds
`bindTestQueue()`: a private, throwaway queue bound to one routing key,
for asserting a service actually republished something onto the wire —
not just that its own HTTP-visible state changed. `payment-to-billing`
uses it to confirm `invoice.paid.v1` really carries `subscription_id`
back, which is the entire point of that translation hop.

Concretely, for `billing-to-subscription` (next):

1. Copy `tests/integration/payment-to-billing/` as a starting point:
   same `docker-compose.yaml` shape, same `tests/support/` dependency
   via its `path` repository.
2. Swap in `billing-api` + `billing-outbox` and `subscription-api` +
   `subscription-consumer` (`subscription-events:consume` already
   binds `invoice.paid.v1` and `invoice.payment_failed.v1`).
3. The test: seed an Invoice the same way (directly-published
   `subscription.created.v1`) *and* a Subscription — Subscription's
   own consumer looks up the Subscription by ID from the event payload,
   so one needs to exist first, and there's no HTTP endpoint to fake
   that either. Either publish a matching `subscription.created.v1`
   through subscription-api's real create flow (bringing
   subscription-service itself into this stack, since it's the only
   thing that can actually create a Subscription row) or seed one more
   directly — decide once the actual constraint is visible, the same
   way `payment-to-billing`'s design changed once its own constraint
   became clear. Then `eventually()` assert the Subscription moved to
   `status: active` via `GET /subscriptions` on subscription-service.

Once `billing-to-subscription` exists, the first `tests/e2e/` scenario
(`successful-subscription`) is mostly assembling every slice's
`docker-compose.yaml` services into one stack and writing one test that
walks the whole chain via HTTP — not new integration work, just
composition.

## Asynchronous assertions

See ADR 0004, "Asynchronous assertions: poll, don't sleep." The
`eventually()` helper lives in [`tests/support/`](../../tests/support/)
as of `payment-to-billing`, the third slice to need it —
`subscription-to-billing` and `billing-to-payment` still have their own
earlier, identical local copies in `tests/Support/`; migrating them to
the shared package is a follow-up, not done yet.

## Fake providers

Not built yet. Needed before any E2E or resilience test that touches
Payment or Notification: a `PAYMENT_GATEWAY=fake` adapter with
deterministic outcomes (a known token/card → success, decline, or
timeout) and a `NOTIFICATION_DRIVER=fake` adapter that records what it
"sent" instead of calling a real provider. Payment already has a
`fake` provider for its automatic first-attempt processing (see the
`provider: "fake"` field in payment records) — the E2E-facing piece
that's missing is deterministic *control* over the outcome from the
test side, not the fake adapter's existence.
