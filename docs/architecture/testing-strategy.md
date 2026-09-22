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
| Contract | one event's schema, producer side | no | no | no | `services/*/tests/Unit/Application/*/IntegrationEvents/` |
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
| Contract | **Producer side done: all 16 events in the [event catalog](event-catalog.md).** One test per `IntegrationEvent` class, under each service's own `tests/Unit/Application/*/IntegrationEvents/` (no new top-level directory — Contract needs no DB, no RabbitMQ, no other services, so it fits the existing per-service Unit suites exactly as ADR 0004 predicted). Each asserts `fromDomainEvent()` (or, for `InvoicePaymentFailedIntegrationEvent`, `of()` — the one event built directly from a handler's inputs rather than a domain event) maps every field into the exact wire shape documented in the catalog, and that two events built from the same input still get distinct `event_id`s. Consumer side not separately built: every `Consume*Command`'s own Integration test already exercises this from a hand-built wire-format payload (e.g. `SubscriptionCreatedConsumerTest`'s `aSubscriptionCreatedPayload()`) — it also touches a real DB, so it's categorized as Integration, not Contract, but it's already covering the same question. |
| E2E (`tests/e2e/`) | **Two scenarios done: `successful-subscription`, `failed-payment`.** Both use all seven services, real Postgres, real RabbitMQ, no direct-publish shortcuts. `successful-subscription`: Merchant → Customer → Product/Price → Subscription → Invoice → Payment → Subscription Active → Notification, walked entirely through real HTTP. `failed-payment`: same chain, but the Price's amount is `FakePaymentGateway`'s reserved decline-trigger value, so the charge is guaranteed to decline — proves the Invoice stays Open, the Payment ends up Failed with a real failure code, and the Subscription stays Pending rather than PastDue. See their own READMEs: [successful-subscription](../../tests/e2e/successful-subscription/README.md), [failed-payment](../../tests/e2e/failed-payment/README.md). |
| Resilience (`tests/resilience/`) | **All four originally planned scenarios done.** `duplicate-delivery`: the same `event_id` published twice; proves Billing's Inbox actually stops the second one from creating a duplicate Invoice — verified live that `billing-consumer` genuinely processed both deliveries (RabbitMQ has no concept of "already seen this"), not that a race meant the second one never arrived. `outbox-recovery`: the test stops `billing-outbox` itself (via `docker compose stop`), creates an Invoice while it's down, then proves the missed row reaches the wire once it's running again. `rabbitmq-outage`: the test stops the broker itself; proves creating a Subscription isn't affected at all (the HTTP create flow never resolves `AMQPChannel`), then proves both the outbox relay and the consumer recover their own connections once RabbitMQ is back — verified live via genuine `Connection refused` errors in both workers' own logs while it was down. `consumer-crash`: kills `billing-consumer` for real, timed via a small, additive, off-by-default delay hook in `ConsumeBillingEventsCommand` to land precisely between its DB commit and its AMQP ack, so RabbitMQ genuinely redelivers the message rather than simulating a duplicate — proves the restarted consumer's own Inbox guard stops it from creating a second Invoice. See their own READMEs: [duplicate-delivery](../../tests/resilience/duplicate-delivery/README.md), [outbox-recovery](../../tests/resilience/outbox-recovery/README.md), [rabbitmq-outage](../../tests/resilience/rabbitmq-outage/README.md), [consumer-crash](../../tests/resilience/consumer-crash/README.md). |
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

`consumer-crash` closed the last of the four originally planned
resilience scenarios, and was the hardest one: unlike
`duplicate-delivery`, `outbox-recovery`, and `rabbitmq-outage`, it
needed to stop a worker at a precise point *mid-transaction* — after
its DB commit, before its AMQP ack — not between polling loop
iterations, where `docker compose stop` alone isn't precise enough.
That window is normally microseconds, far too narrow for any external,
black-box test (log-watch-then-kill) to land inside reliably. Rather
than accept a flaky test, this slice made a small, deliberate,
justified change to production code itself — the first (and, so far,
only) resilience test to do so: `ConsumeBillingEventsCommand` now logs
`"Processed event {id}, acking."` right before its ack (independently
useful for diagnosing a stuck ack in production, not just a test hook),
and reads an env var, `CONSUMER_CRASH_TEST_DELAY_MS`, right before that
ack — unset (`0`, a no-op) in every real environment and every other
compose stack in this repo, set only by this test's own
`docker-compose.yaml` (to `5000`), widening the window from
microseconds to seconds specifically so an external test can reliably
observe the log line and kill the container before the ack rather than
after it. Both changes are additive and off by default; the service's
full test suite and Pint both stayed green with them in place. This is
also where `DockerCompose` (copied for `outbox-recovery`, then
`rabbitmq-outage`) moved into [`tests/support/`](../../tests/support/)
on its third use, the same copy-first, share-on-third-use discipline
`eventually()` and `AmqpTestClient` followed earlier — gaining a
`kill(service, signal)` method the two `stop`/`start` copies before it
never needed. Verified live: `"Processed event <id>, acking."` appeared
**twice** in `billing-consumer`'s logs for the one event this test
published — once for the killed attempt, once for the redelivered one
that actually completed — while `"Consumed 1 message(s)."` (reachable
only *after* a successful ack) appeared exactly **once**; `docker
compose ps` showed the container `Up` for less time than it had
existed, confirming a genuine kill-and-restart rather than a no-op.

`failed-payment` closed the "Fake providers" gap the previous version
of this doc flagged: `ChargeRequest` carries only an attempt id and an
amount, no card/token concept a test could set to "always decline", and
adding one would have meant threading a "how should this fail" concept
through Subscription and Billing too, just to reach a gateway three
hops downstream. Instead, `FakePaymentGateway` gained one deterministic
trigger already available at every layer between an E2E test and the
gateway without any new plumbing: the amount itself. A charge for
exactly `66660000` minor units (any currency) — `DECLINE_TRIGGER_AMOUNT_MINOR_UNITS`,
deliberately unmistakable so no real price ever lands on it by
accident — always declines with `card_declined`; everything else still
always succeeds. That's the entire change; nothing outside
`FakePaymentGateway` itself needed to move. The scenario built on it is
otherwise pure composition of hops `tests/integration/` already proved
in isolation (`subscription-to-billing`, `billing-to-payment`,
`billing-to-subscription`'s own guard-condition test) — what only a
full E2E run adds is confirming the real, gateway-driven decline leaves
the Invoice Open, the Payment Failed, and the Subscription Pending
(never PastDue, since it never reached Active), with the same
documented `sleep()`-over-`eventually()` exception as
`payment-to-notification` and `consumer-crash` used to prove those
three are absences, not "not yet". Verified live:
`billing-consumer` consumed exactly 2 messages (`subscription.created.v1`,
`payment.failed.v1`), `payment-outbox` published exactly 1
(`payment.failed.v1`), `subscription-consumer` consumed exactly 1
(`invoice.payment_failed.v1`), and `notification-ingest-consumer`
consumed **0** — direct confirmation it never saw anything, not just
that nothing showed up over HTTP.

`tests/e2e/overdue-subscription/` turned out to need a real production
feature this platform doesn't have yet — a scheduled job that creates
the *next* billing cycle's Invoice for an Active subscription past its
period end. `CreateInvoiceHandler` only ever fires once, on
`subscription.created.v1`; there's no renewal mechanism to seed a
second cycle through, so the scenario can't be built as a test alone.
Building one is real domain/application work, not testing
infrastructure, so it was deliberately deferred rather than folded into
this effort — the Active → PastDue transition it would have exercised
is already fully covered anyway, by
`tests/integration/billing-to-subscription/`'s own second test (drives
a subscription to Active via a direct-published `invoice.paid.v1`, then
fails it).

With the E2E and Resilience branches both at a natural pause, the
Contract layer's producer side closed the gap ADR 0004 called out from
the start: every `IntegrationEvent` class across the platform — all 16
events in the [event catalog](event-catalog.md), not just the 6 in the
platform's wired core chain — now has its own test asserting
`fromDomainEvent()` maps every field into exactly the wire shape the
catalog documents, and that two events built from the same input still
get distinct `event_id`s (redelivery/retry must never silently
collapse two attempts into one). Ten of the sixteen were new
this slice (the four subscription-service events, four
billing-service, two payment-service); catalog-service and
customer-service already had the other six, unknowingly following the
exact same shape — proof the pattern was sound before it had a name.
`InvoicePaymentFailedIntegrationEvent` needed a slightly different
test: it's the one event with no domain event to translate from (the
Invoice itself doesn't change state on a failed payment), built
directly via `of()` from the relay handler's own inputs instead of
`fromDomainEvent()`. No new top-level directory was needed — per ADR
0004's own layer table, Contract needs no DB, no RabbitMQ, no other
services, so it fits directly into each service's existing
`tests/Unit/Application/*/IntegrationEvents/`, the same place
catalog-service and customer-service were already putting it.
Consumer-side contract coverage wasn't separately built: every
`Consume*Command`'s own Integration test already asks the same
question from a hand-built wire-format payload (e.g.
`SubscriptionCreatedConsumerTest`'s `aSubscriptionCreatedPayload()`) —
it also touches a real repository, so it's categorized as Integration
rather than Contract, but nothing was actually missing there.

`eventually()`'s and `DockerCompose`'s remaining un-migrated local
copies — `subscription-to-billing`/`billing-to-payment` for the
former, `outbox-recovery`/`rabbitmq-outage` for the latter — have since
been migrated onto the shared `tests/support/` package, closing that
cleanup follow-up: every consumer of either helper now shares the one
implementation, with no behavior change (verified live per slice; see
"Asynchronous assertions" below).

What's next, none of it blocking what exists today:

- `tests/e2e/overdue-subscription/` — blocked on the recurring-billing
  feature described above; out of scope for this testing effort until
  that feature exists.
- Component layer — still not started at all; see its own row in the
  status table above.

## Asynchronous assertions

See ADR 0004, "Asynchronous assertions: poll, don't sleep." The
`eventually()` helper lives in [`tests/support/`](../../tests/support/)
as of `payment-to-billing`, the third slice to need it —
`subscription-to-billing` and `billing-to-payment` originally had their
own earlier, identical local copies in `tests/Support/`; both have
since been migrated onto the shared package, so every caller across the
platform now shares the one implementation.

## Fake providers

Safe for both the happy path and (for payments) the failure path.
`IPaymentGatewayPort` and `IEmailSenderPort` are both bound
unconditionally to their `Fake*` implementations in each service's own
`ServiceProvider` — not gated by environment, not swapped for a real
provider anywhere yet — so no E2E test can accidentally hit a live
payment processor or send a real email today; there's no code path to
one.

`FakePaymentGateway::charge()` returns success for every amount except
one: `FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS`
(`66660000` minor units, any currency) always declines with
`card_declined`. That single deterministic trigger, not a card/token
field, is what `tests/e2e/failed-payment/` drives from the test side —
see that scenario's own README and "Building the next slice" above for
why the amount, not a new field threaded through three services, is
the trigger. `FakeEmailSender::send()` still always returns success
unconditionally; no test yet needs it to fail, and nothing currently
asks it to.
