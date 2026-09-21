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
| `payment-to-billing` | Not built. `payment.succeeded.v1`/`payment.failed.v1` → Billing translates to `invoice.paid.v1`/`invoice.payment_failed.v1`. |
| `billing-to-subscription` | Not built. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription activates/marks PastDue. |
| `payment-to-notification` | Not built. `payment.succeeded.v1` → Notification delivers a receipt. |

### Everything else in the pyramid

| Layer | Status |
| --- | --- |
| Component | Not built. Would live per-service, e.g. `services/subscription-service/tests/Component/`. |
| Contract | Not built. One producer-side test per event in the [event catalog](event-catalog.md), one consumer-side test per service that consumes it. |
| E2E (`tests/e2e/`) | Not built. Needs `payment-to-billing` and `billing-to-subscription` wired first — see "Building the next slice" below. |
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

Concretely, for `payment-to-billing` (next):

1. Copy `tests/integration/billing-to-payment/` as a starting point:
   same `docker-compose.yaml` shape, same standalone Pest project, same
   `eventually()` helper — this will be the second time `eventually()`
   is copied rather than shared; extract it into a common location
   (e.g. a `tests/support/` Composer package) on the *third* slice
   rather than before it's actually needed twice over.
2. Swap in `payment-api` + `payment-outbox` (payment already publishes
   `payment.succeeded.v1`/`payment.failed.v1`) and `billing-api` +
   `billing-consumer` (`billing-events:consume` already binds both
   routing keys).
3. The test: publish `payment.succeeded.v1` directly (payment-service
   has no direct "mark this payment succeeded" HTTP endpoint either —
   same reasoning as above), matching the wire format
   `PaymentSucceededIntegrationEvent` produces, then `eventually()`
   assert the targeted Invoice moved to `status: paid` via
   `GET /invoices` on billing-service, and that `invoice.paid.v1` was
   in fact republished (either by checking `billing-to-subscription`
   once it exists, or by asserting the message landed on a
   test-declared queue bound to that routing key).

Once `payment-to-billing` and `billing-to-subscription` both exist, the
first `tests/e2e/` scenario (`successful-subscription`) is mostly
assembling their `docker-compose.yaml` services into one stack and
writing one test that walks the whole chain via HTTP — not new
integration work, just composition.

## Asynchronous assertions

See ADR 0004, "Asynchronous assertions: poll, don't sleep." The
`eventually()` helper is copied into each slice's own
`tests/Support/` today — `subscription-to-billing` and
`billing-to-payment` each have their own identical copy. Extract it
into a shared location (e.g. a `tests/support/` Composer package) when
`payment-to-billing` needs it too, rather than copying it a third
time.

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
