# 4. Testing strategy: the pyramid, and where each layer runs

*[Русская версия](0004-testing-strategy.ru.md)*

## Status

Accepted

## Context

Every service already has tests, but they all sit under three names
per service — `Unit`, `Integration`, `Feature` — and nothing at the
monorepo level tests what actually makes this a distributed system:
that Subscription's Outbox really reaches Billing's Inbox through a
real RabbitMQ, that a duplicate delivery really is a no-op, that the
whole `Subscription → Invoice → Payment → Notification` business flow
actually completes.

"Add more tests" isn't a decision by itself. Three genuinely different
things were all getting the same treatment:

- "does this Handler make the right calls against fakes" (fast, no
  infrastructure, should run on every commit)
- "does this Eloquent repository actually round-trip through Postgres"
  (needs real infrastructure, still one service)
- "does the whole platform complete a purchase" (needs every service
  running, is expensive, and shouldn't gate every commit)

Conflating them produces either a suite too slow to run locally, or one
that skips the layer that actually proves the distributed-systems
patterns (Outbox, Inbox, eventual consistency) work at all.

## Decision

### The layers

A test belongs to exactly one of these, based on what it needs running
— not on which directory happens to be convenient:

| Layer | Verifies | Real DB | Real RabbitMQ | Other services |
| --- | --- | --- | --- | --- |
| Unit | a Domain entity or value object | no | no | no |
| Application | a Handler/use case, against fakes | no | no | no |
| Integration | one adapter (repository, HTTP gateway, RabbitMQ publisher) | yes | sometimes | no |
| Component | one whole service, its own HTTP/console boundary in | yes | yes | stubbed |
| Contract | one event's schema, producer or consumer side | no | no | no |
| Service integration | 2-3 services through a real broker | yes | yes | yes (2-3) |
| E2E | a full business flow across the platform | yes | yes | yes (all) |
| Resilience | a specific failure mode (crash, redelivery, outage) | yes | yes | yes |

Unit and Application tests should vastly outnumber everything below
them — they're what actually catches a broken business rule, and they
cost nothing to run. Every layer below Integration gets progressively
more expensive and answers a progressively narrower question; none of
them substitutes for the one above it "just running with real
infrastructure instead of fakes."

### Mapping onto what already exists, per service

No mechanical rename across seven services for this decision alone —
existing suites already map cleanly:

- `tests/Unit/Domain/` → **Unit**
- `tests/Integration/Application/` → **Application** (the directory
  name predates this ADR; it already means "Handler + fakes, no
  infrastructure" in every service, which is what this table calls
  Application)
- `tests/Integration/{Gateways,Messaging,Persistence,Transaction}/` →
  **Integration** (real Postgres via the service's own test database,
  a real RabbitMQ connection where the adapter needs one)
- `tests/Feature/{Http,Console}/` → Laravel's own idiomatic in-process
  request-cycle test: real routing/middleware/FormRequest/Controller,
  `Http::fake()` for outbound calls. Distinct from **Component** below
  (a live, separately-running process reached over the network, with a
  live stub server standing in for its dependencies) — keep both, they
  answer different questions.
- `tests/Architecture/` → unchanged, enforces the dependency-direction
  rules Clean Architecture requires.

**Component**, **Contract**, **Service integration**, **E2E** and
**Resilience** are new. Service integration and E2E/Resilience don't
belong inside any one service's `tests/` — they live at the monorepo
root, since no single service's test run should require building and
booting six others.

### Monorepo test layout

```text
tests/
├── integration/            # Service integration: 2-3 real services, real broker
│   ├── subscription-to-billing/
│   ├── billing-to-payment/
│   └── payment-to-billing/
├── e2e/                     # Full business flows, every service, fake providers
│   ├── successful-subscription/
│   ├── failed-payment/
│   └── overdue-subscription/
└── resilience/              # A specific failure mode, not a business scenario
    ├── duplicate-delivery/
    ├── consumer-crash/
    ├── rabbitmq-outage/
    └── outbox-recovery/
```

Each `tests/integration/<slice>/` and `tests/e2e/<scenario>/` directory
is its own small, standalone project: a `docker-compose.yaml` bringing
up exactly the services that scenario needs (not all seven, unless it's
a full E2E), and a Pest project with its own `composer.json` — no
Laravel bootstrap, no shared vendor with any service, since these
tests are black-box HTTP clients of already-running processes. See
[`tests/integration/subscription-to-billing/`](../../tests/integration/subscription-to-billing/)
for the first one.

### Asynchronous assertions: poll, don't sleep

A request that triggers an async chain (HTTP call → Outbox → RabbitMQ →
a separate consumer process) can't be asserted on immediately — the
201 response only proves the first hop, not that the whole chain ran.
A fixed `sleep(N)` before asserting is both slow (always pays the worst
case) and still flaky (a loaded CI runner can still lose the race).
Every service integration, E2E and resilience test instead polls its
final assertion until it passes or a timeout elapses — see
[`eventually()`](../../tests/support/src/eventually.php) in the shared
test-support package.

### Assert through HTTP, not by reaching into another service's database

A Component test asserting against its own service's database is fine
— it's testing that service's internals. A Service integration or E2E
test reaching into another service's schema (`SELECT * FROM
billing.invoices`) is not: it couples the test to internal storage
detail no client is allowed to depend on, and defeats the point of
database-per-service. These tests call the other service's own HTTP
API instead (`GET /v1/invoices/{id}`), exactly like a real client
would.

### Fake providers, not real ones, for anything external

E2E and resilience tests must not call a real payment processor or send
a real email. Both Payment and Notification services get a fake
provider adapter selectable by config (`PAYMENT_GATEWAY=fake`,
`NOTIFICATION_DRIVER=fake`) that supports deterministic outcomes (a
known token/card → success, decline, or timeout) so failure-path
scenarios are actually reproducible, not dependent on a sandbox
provider's mood.

### Docker Compose for business tests, Kubernetes for platform tests

Service integration and E2E tests run on Docker Compose, not
Kubernetes — they're proving the business system works, not that
Kubernetes does. A `kind`-based test suite exists separately, and
checks a different set of questions entirely: does the Ingress route
correctly, do rolling updates stay available, does a CronJob fire, does
a worker actually scale — infrastructure questions, not business ones.
It runs its own one or two smoke business scenarios (create a
subscription, eventually active) rather than the full E2E catalog.

### CI split

Unit, Application, Architecture, Integration, Component and Contract
run on every PR — this whole set should complete in low single-digit
minutes. Service integration runs on every PR too, scoped to whichever
slices the changed services touch. Full E2E and Resilience are too
expensive to gate every commit (booting seven services plus
infrastructure) and run on `main` and nightly instead.

## Consequences

Gets easier: a broken business rule fails fast in an Application test
seconds after it's written, instead of only surfacing in an expensive
E2E run minutes later. Adding a new event consumer means writing a
Contract test for it, not hoping the next E2E run happens to exercise
it. The distributed-systems patterns this whole platform is built
around — Outbox, Inbox, eventual consistency, idempotent redelivery —
now have tests that can actually fail if they stop working, instead of
only being provable by manually curling a running cluster (which is how
every cross-service chain in this repo has been verified so far).

Gets harder: more moving parts to keep straight (which of eight layers
does a new test belong in), and Service integration / E2E tests need
their own Docker Compose stacks and standalone Pest projects, not just
another test class dropped into an existing service. `docker compose
build` for four-plus services adds real wall-clock time locally and in
CI, even though it's still far cheaper than the alternative (only
finding out the wiring is broken from a manual `curl` against a live
cluster, or from a customer).

Follow-up work, explicitly not done by this ADR: the remaining service
integration slices (Billing → Payment, Payment → Billing → Subscription
→ Notification), Component tests for any service, Contract tests for
the event catalog, the full E2E catalog, the resilience suite, and the
`kind`-based platform smoke tests. Built one vertical slice at a time,
same as every other part of this platform — see
[`docs/architecture/testing-strategy.md`](../architecture/testing-strategy.md)
for the living version of this plan as it's filled in.
