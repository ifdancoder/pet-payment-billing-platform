# Testing strategy

*[Русская версия](testing-strategy.ru.md)*

[ADR 0004](../adr/0004-testing-strategy.md) defines the test boundaries.

| Layer | Location | Boundary |
| --- | --- | --- |
| Unit | `services/*/tests/Unit` | Domain behavior without infrastructure |
| Application | `services/*/tests/Application` | Use cases with test doubles |
| Integration | `services/*/tests/Integration` | One service with real adapters |
| Feature | `services/*/tests/Feature` | One Laravel application through HTTP or console |
| Contract | `services/*/tests/Contract` | Integration-event wire shape |
| Component | `tests/component` | One running service with real PostgreSQL/RabbitMQ and HTTP stubs |
| Service integration | `tests/integration` | A focused boundary between running services |
| E2E | `tests/e2e` | Complete business workflows |
| Resilience | `tests/resilience` | Broker, worker, and redelivery failures |
| Platform smoke | `tests/kind` | Ingress and Kubernetes workload behavior |
| Load smoke | `tests/load` | Bounded k6 traffic against the full Compose stack |

## Repository suites

Component suites cover Subscription with stubbed Customer/Catalog lookups and
Notification with a stubbed Customer lookup. Service integration suites cover
each boundary in the subscription-to-payment event chain. E2E suites cover a
successful purchase, an initial payment decline, and a failed renewal. Resilience
suites cover duplicate delivery, outbox recovery, a RabbitMQ outage, and a
consumer crash after commit but before acknowledgement.

Each repository-level suite is an independent Pest project and Compose stack.
Run all of them with `make test-compose`; each stack and its volumes are removed
after the suite.

## Test rules

- Exercise only the services required for the boundary under test.
- Publish a seed event directly when the upstream producer is outside that
  boundary and already has separate coverage.
- Assert another service's state through its HTTP API, not its database.
- Use `eventually()` for asynchronous positive assertions. A bounded fixed wait
  is acceptable only when proving that an effect does not occur.
- Use fake external payment and email providers. Do not call live providers from
  the test suites.
- Keep shared orchestration helpers in `tests/support` after they are used by
  more than one suite.

The kind suite expects an already deployed cluster. It verifies ingress routing,
a rolling restart of `billing-api`, and one successful subscription flow. It is
not a replacement for the Compose business suites.
