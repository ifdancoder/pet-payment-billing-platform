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
| `billing-to-payment` | Not built. `invoice.created.v1` → Payment auto-creates and processes a Payment. |
| `payment-to-billing` | Not built. `payment.succeeded.v1`/`payment.failed.v1` → Billing translates to `invoice.paid.v1`/`invoice.payment_failed.v1`. |
| `billing-to-subscription` | Not built. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription activates/marks PastDue. |
| `payment-to-notification` | Not built. `payment.succeeded.v1` → Notification delivers a receipt. |

### Everything else in the pyramid

| Layer | Status |
| --- | --- |
| Component | Not built. Would live per-service, e.g. `services/subscription-service/tests/Component/`. |
| Contract | Not built. One producer-side test per event in the [event catalog](event-catalog.md), one consumer-side test per service that consumes it. |
| E2E (`tests/e2e/`) | Not built. Needs `billing-to-payment` and `payment-to-billing` wired first — see "Building the next slice" below. |
| Resilience (`tests/resilience/`) | Not built. |
| `kind`-based platform smoke tests | Not built. Separate from all of the above — see ADR 0004, "Docker Compose for business tests, Kubernetes for platform tests." |

## Building the next slice

Don't jump straight to the full E2E. Each service integration slice
gets built, proven, and merged on its own, the same way the RabbitMQ
event chain itself was built one vertical slice at a time (see the
[event catalog](event-catalog.md)'s own history). Concretely, for
`billing-to-payment`:

1. Copy `tests/integration/subscription-to-billing/` as a starting
   point: same `docker-compose.yaml` shape (Postgres, RabbitMQ, the two
   services' `-api` and whichever `-consumer`/`-outbox` roles the
   scenario needs), same standalone Pest project, same `eventually()`
   helper.
2. Swap in `billing-api` + `billing-outbox` (billing must actually
   publish `invoice.created.v1` — it already does, via
   `InvoiceCreatedIntegrationEvent`) and `payment-api` +
   `payment-consumer` (`invoice-created:consume`).
3. The test: create an Invoice directly through billing's own HTTP API
   (no need to go through Subscription for this slice — that boundary
   is already covered by `subscription-to-billing`), then `eventually()`
   assert a Payment appears via `GET /payments` on payment-service,
   `status: succeeded` (the fake payment provider auto-succeeds).

Once `billing-to-payment`, `payment-to-billing` and
`billing-to-subscription` all exist, the first `tests/e2e/` scenario
(`successful-subscription`) is mostly assembling their
`docker-compose.yaml` services into one stack and writing one test that
walks the whole chain via HTTP — not new integration work, just
composition.

## Asynchronous assertions

See ADR 0004, "Asynchronous assertions: poll, don't sleep." The
`eventually()` helper lives in
[`tests/integration/subscription-to-billing/tests/Support/eventually.php`](../../tests/integration/subscription-to-billing/tests/Support/eventually.php)
today; once a second slice needs it, it moves to a shared location
(e.g. a `tests/support/` Composer package) rather than being copied a
third time.

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
