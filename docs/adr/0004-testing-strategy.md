# 4. Testing strategy

*[Русская версия](0004-testing-strategy.ru.md)*

## Status

Accepted

## Context

Per-service tests do not prove that outbox, RabbitMQ, inbox, and downstream state
changes work together. Full-platform tests are too expensive and too broad to
replace focused domain, adapter, and boundary tests.

## Decision

Tests are separated by runtime boundary:

| Layer | Boundary |
| --- | --- |
| Unit | Domain entity or value object without infrastructure |
| Application | Handler or use case with test doubles |
| Integration | One adapter with its real infrastructure |
| Feature | One Laravel application through HTTP or console |
| Architecture | Dependency rules within one service |
| Contract | Producer integration-event wire shape |
| Component | One running service with real database/broker and stubbed HTTP dependencies |
| Service integration | A focused boundary between running services |
| E2E | A complete business workflow across all required services |
| Resilience | One crash, outage, retry, or redelivery condition |
| Platform smoke | Kubernetes routing and workload behavior |

Repository-level suites are independent Pest projects with disposable Compose
stacks. Asynchronous positive assertions poll with `eventually()`. Cross-service
state is read through public HTTP APIs, not another service's database. External
payment and email providers use deterministic fakes. Kubernetes tests cover
platform behavior; Compose tests cover business behavior.

CI runs service tests, documentation and Compose contract checks, all Compose
suites, and the bounded load smoke on pull requests and `main`. The kind suite
runs separately against an existing cluster.

## Consequences

Failures can be localized to a domain rule, adapter, service boundary, workflow,
or failure mode. Repository-level suites cost more to build and run, so each one
contains only the services required by its boundary. Shared orchestration code
belongs in `tests/support` once more than one suite needs it.
