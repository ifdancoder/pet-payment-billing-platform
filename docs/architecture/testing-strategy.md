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
| `billing-to-subscription` | **Done.** Two tests: a directly-published `invoice.paid.v1` activates a real, HTTP-created Pending subscription; a directly-published `invoice.payment_failed.v1` marks it PastDue, but only once it's genuinely Active first (exercises `HandleInvoicePaymentFailedHandler`'s own guard, not just the transition). See its own [README](../../tests/integration/billing-to-subscription/README.md). |
| `payment-to-notification` | Not built. `payment.succeeded.v1` → Notification delivers a receipt. Doesn't block the first E2E scenario. |

### Everything else in the pyramid

| Layer | Status |
| --- | --- |
| Component | Not built. Would live per-service, e.g. `services/subscription-service/tests/Component/`. |
| Contract | Not built. One producer-side test per event in the [event catalog](event-catalog.md), one consumer-side test per service that consumes it. |
| E2E (`tests/e2e/`) | Not built, but every service integration slice its first scenario needs now exists — see "Building the next slice" below. |
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

`billing-to-subscription` resolved its own version of the same
question cleanly, because Subscription (unlike Invoice or Payment) has
a real `POST /subscriptions` endpoint: no direct-publish trick was
needed to seed one, just a real request through the real create flow —
which is why `customer-service` and `catalog-service` are in that
stack even though neither is the boundary under test (that flow calls
them synchronously). billing-service itself is *not* in that stack —
its publish of both events is already covered by `payment-to-billing`,
so the test publishes `invoice.paid.v1`/`invoice.payment_failed.v1`
directly, same reasoning as every slice before it. It's also the first
slice to assert a *guard condition*, not just a transition:
`HandleInvoicePaymentFailedHandler` only fires `Active` → `PastDue`, so
its second test drives the subscription to genuinely Active first
(publishing `invoice.paid.v1` and waiting) before publishing
`invoice.payment_failed.v1` — a Pending subscription's first-ever
failed payment is supposed to stay Pending, and only a test that
actually reaches Active first can tell the two cases apart.

With `subscription-to-billing`, `billing-to-payment`,
`payment-to-billing` and `billing-to-subscription` all done, every
service integration slice the first `tests/e2e/` scenario
(`successful-subscription`) needs now exists. Building it is mostly
assembling every slice's `docker-compose.yaml` services into one stack
(all seven services, not "2-3" — see the pyramid table) and writing one
test that walks the whole chain via HTTP: create a Subscription for
real, `eventually()` assert it reaches `active`, without any
direct-publish shortcuts — an E2E test proves the whole system
produces the event, not that a service can consume one handed to it.
`payment-to-notification` isn't a hard blocker (the scenario can assert
up through Subscription `active` without it) but is worth having before
declaring `successful-subscription` "done," since the receipt is part
of the actual business flow. Fake providers turned out *not* to be a
blocker at all, on closer look — see "Fake providers" below.

## Asynchronous assertions

See ADR 0004, "Asynchronous assertions: poll, don't sleep." The
`eventually()` helper lives in [`tests/support/`](../../tests/support/)
as of `payment-to-billing`, the third slice to need it —
`subscription-to-billing` and `billing-to-payment` still have their own
earlier, identical local copies in `tests/Support/`; migrating them to
the shared package is a follow-up, not done yet.

## Fake providers

Already safe for the happy path, less complete for failure paths.
`IPaymentGatewayPort` and `IEmailSenderPort` are both bound
unconditionally to their `Fake*` implementations in each service's own
`ServiceProvider` — not gated by environment, not swapped for a real
provider anywhere yet — so no E2E test can accidentally hit a live
payment processor or send a real email today; there's no code path to
one. `FakePaymentGateway::charge()` and `FakeEmailSender::send()` both
always return success unconditionally, though, which is exactly what
`successful-subscription` needs and nothing more.

What's actually missing is *deterministic control* over the outcome
from the test side, needed for `tests/e2e/failed-payment/` and any
resilience scenario that wants a payment to fail on purpose (a known
token/card → decline or timeout, selectable per request) rather than
always succeeding. Until that exists, only the happy-path E2E scenario
is buildable; failure-path scenarios need this piece first.
