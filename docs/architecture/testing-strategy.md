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
| `payment-to-notification` | **Done.** Two tests: the happy path (a directly-published `payment.succeeded.v1` eventually delivers a Sent email receipt), and a negative case proving `PaymentSucceededConsumer`'s own guard — an unknown `customer_id` records neither an Inbox entry nor a Notification, so a redelivery can simply retry the lookup later. See its own [README](../../tests/integration/payment-to-notification/README.md). |

### Everything else in the pyramid

| Layer | Status |
| --- | --- |
| Component | Not built. Would live per-service, e.g. `services/subscription-service/tests/Component/`. |
| Contract | Not built. One producer-side test per event in the [event catalog](event-catalog.md), one consumer-side test per service that consumes it. |
| E2E (`tests/e2e/`) | **First scenario done: `successful-subscription`.** All seven services, real Postgres, real RabbitMQ, no direct-publish shortcuts — Merchant → Customer → Product/Price → Subscription → Invoice → Payment → Subscription Active → Notification, walked entirely through real HTTP. See its own [README](../../tests/e2e/successful-subscription/README.md). |
| Resilience (`tests/resilience/`) | **Three scenarios done.** `duplicate-delivery`: the same `event_id` published twice; proves Billing's Inbox actually stops the second one from creating a duplicate Invoice — verified live that `billing-consumer` genuinely processed both deliveries (RabbitMQ has no concept of "already seen this"), not that a race meant the second one never arrived. `outbox-recovery`: the test stops `billing-outbox` itself (via `docker compose stop`), creates an Invoice while it's down, then proves the missed row reaches the wire once it's running again. `rabbitmq-outage`: the test stops the broker itself; proves creating a Subscription isn't affected at all (the HTTP create flow never resolves `AMQPChannel`), then proves both the outbox relay and the consumer recover their own connections once RabbitMQ is back — verified live via genuine `Connection refused` errors in both workers' own logs while it was down. See their own READMEs: [duplicate-delivery](../../tests/resilience/duplicate-delivery/README.md), [outbox-recovery](../../tests/resilience/outbox-recovery/README.md), [rabbitmq-outage](../../tests/resilience/rabbitmq-outage/README.md). |
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
`payment-to-billing` and `billing-to-subscription` all done,
`successful-subscription` turned out to be mostly composition, exactly
as predicted: every `docker-compose.yaml` service each slice already
used, assembled into one 16-container stack (all seven services, not
"2-3" — see the pyramid table), and one test that walks the whole chain
via real HTTP with zero direct-publish shortcuts — create a
Subscription for real, `eventually()` assert Invoice → Paid, Payment →
succeeded, Subscription → `active`, and a receipt Notification, in that
order. Verified live: exactly one message at every hop across the
entire chain (checked in each worker's own logs, not just inferred from
the HTTP assertions passing), no duplicates, no drops.

Building it surfaced one thing worth carrying forward, distinct from
anything a smaller slice would show: with seven services' containers
all starting at once instead of two or three, a startup race that
every `docker-compose.yaml` in this repo actually has — a
`-consumer`/`-outbox` worker depends only on `postgres` being healthy,
not on its own `-api` container's migration having finished — showed up
for the first time as an observed, logged error (`billing-outbox`
querying `outbox_messages` before `billing-api`'s `migrate --force` had
created it). It self-healed within its own retry loop and didn't fail
the test, so it's documented rather than "fixed" — see
`successful-subscription`'s own README for the full reasoning on why
that's the right call for a disposable, single-replica-per-service
compose stack (as opposed to Kubernetes, where a separate migrate Job
exists specifically to rule this out).

`payment-to-notification` closed the last event-boundary slice, and
added something none of the first four needed: a negative test.
`PaymentSucceededConsumer`'s own guard — an unknown customer means
neither the Inbox nor a Notification gets recorded, so redelivery can
just retry the lookup later — is a claim about something *not*
happening, and `eventually()` is built to wait for a condition to
become true, not to prove one stays false. Its second test instead
gives the wrong behavior a real window (a fixed `sleep(3)`, several
real worker loop iterations) before asserting the notification list is
still empty. That's not the `sleep()`-over-`eventually()` anti-pattern
ADR 0004 warns against — it's the only honest way to test an absence;
polling would just mean "hasn't happened *yet*," not "doesn't happen."

With every event boundary in the platform's core chain now covered by
its own service integration slice, `duplicate-delivery` started
`tests/resilience/` — the pyramid's other still-mostly-empty branch, and
a genuinely different kind of test from everything above it. A service
integration slice asks "does this boundary work"; a resilience test
asks "does it keep working when RabbitMQ's own delivery guarantee
actually exercises the ugly case it's *supposed* to handle." It reuses
`billing-consumer` from `subscription-to-billing`/`billing-to-payment`
rather than standing up a new boundary, and needed one small addition
to `AmqpTestClient`: an explicit `eventId` parameter on `publish()`, so
the test can publish the *same* event twice — every other slice was
happy letting it auto-generate a fresh one, since nothing before this
needed to simulate an actual redelivery rather than an independent
event that happens to look similar. Verified live that this wasn't a
cheap pass: `billing-consumer`'s own logs showed it genuinely processed
both deliveries — RabbitMQ has no idea they're "the same event," it
delivered exactly what was published — and only the Inbox's
`recordIfNew()` guard is what kept a second Invoice from existing.

`outbox-recovery` needed a genuinely new capability none of the tests
above it did: the test controls Docker itself, stopping and restarting
`billing-outbox` mid-scenario via `tests/Support/DockerCompose.php` (a
thin `docker compose stop/start` wrapper, local to this test for
now — same copy-first, share-on-third-use discipline as `eventually()`
and `AmqpTestClient`). Stopping the relay *before* creating anything
matters: the Outbox row under test has to be written while the relay
is provably down, not race one that just hasn't reached it yet.
Verified live this was a real stop, not a simulated one: `docker
compose ps` showed the container `Up` for less time than it had existed
— a genuine stop-and-restart, not a no-op — and its logs contained
exactly one successful publish once it came back, for the exact row it
had missed.

`rabbitmq-outage` did exactly what its own predecessor predicted —
reused `outbox-recovery`'s `DockerCompose` helper as a second copy
(stopping `rabbitmq` itself this time, not one consumer of it) — but
asks a sharper question than "does the relay catch up": does creating
a Subscription get affected *at all* by the broker being completely
unreachable. The answer rests on an architectural fact, not just a
retry loop: `subscription-api`'s create flow never resolves
`AMQPChannel` in the first place (it's a lazy Laravel singleton only
`PublishOutboxMessagesCommand` and the `*-events:consume` commands ever
ask for), so there's no code path from that HTTP request to RabbitMQ to
fail on. Verified live, not just read in the code: the write returned
`201 Pending` while `rabbitmq` was confirmed stopped, and — the part
that actually proves this wasn't a lucky timing window — both
`subscription-outbox` and `billing-consumer` logged several genuine
`Connection refused` errors from their own independent retry loops
while the broker was down, each recovering on its very next iteration
once it came back.

What's next, none of it blocking what exists today:

- `tests/e2e/failed-payment/` and `tests/e2e/overdue-subscription/` —
  both need deterministic *control* over the fake payment provider's
  outcome from the test side first (see "Fake providers" below); not
  buildable yet, not because a fake provider doesn't exist, but because
  it can't currently be told to fail on purpose.
- `tests/resilience/consumer-crash/` — the one remaining failure mode,
  and a harder problem than the first three: stopping a worker at a
  precise point mid-transaction (after its DB commit, before its AMQP
  ack) rather than between polling loop iterations, which none of
  `duplicate-delivery`, `outbox-recovery` or `rabbitmq-outage` needed to
  solve — `docker compose stop` alone isn't precise enough on its own.
- Component and Contract layers — still not started at all; see their
  own rows in the status table above.

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
