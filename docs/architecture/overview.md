# Architecture overview

*[Русская версия](overview.ru.md)*

How the billing platform is meant to fit together.

## System context

The platform is a set of independently deployable Laravel services that
together handle billing, subscriptions and payments for merchants. No
monolith: each business capability owns its data and sits behind its own
service boundary.

Traffic comes in through a single API gateway (Nginx locally, an
ingress-nginx `Ingress` on Kubernetes, see [`kubernetes.md`](kubernetes.md))
and gets routed to the owning service. See
[ADR 0001](../adr/0001-api-gateway-routing.md) for the routing table,
the public `/v1/...` vs. internal `/api/v1/...` split, and why the
gateway itself never authenticates a request.

## Service boundaries

| Service | Responsibility |
| --- | --- |
| Identity Service | Merchant authentication and authorization |
| Customer Service | Customers and billing profiles |
| Catalog Service | Products, prices and discounts |
| Subscription Service | Subscription lifecycle |
| Billing Service | Invoices and billing cycles |
| Payment Service | Payments, refunds and payment providers |
| Notification Service | Asynchronous customer notifications |

All seven exist under `services/`, each an independently runnable
Laravel app with its own database, not yet containerized (see
[`kubernetes.md`](kubernetes.md) and the root README's roadmap for
where Docker/Kubernetes land relative to everything else).

## Data ownership

Each service owns its persistence model and is the only thing that
writes to it. No service reads another service's database directly.
Locally everything might share one PostgreSQL instance for convenience,
but the ownership boundary is logical, not physical, and it has to hold
regardless of how things are actually deployed.

## Communication

- **Synchronous**: direct HTTP calls, used when the caller needs an
  answer right away.
- **Asynchronous**: integration events over RabbitMQ, used for
  cross-service workflows and propagating state changes (a subscription
  change triggering invoice generation, for example). See
  [ADR 0002](../adr/0002-rabbitmq-messaging.md) for the messaging
  contract (delivery semantics, envelope, naming, queue topology) and
  [`event-catalog.md`](event-catalog.md) for every event that exists
  today, who publishes it, and who actually consumes it.

## Reliability patterns

These aren't an afterthought:

- transactional outbox, so events get published reliably alongside local
  writes
- inbox / idempotent consumers, so a redelivered event doesn't cause
  duplicate side effects
- idempotency keys on mutating client-facing endpoints
- retries with dead-letter queues for messages that just won't process
- a saga / process manager for workflows that span more than one service
  and can't be a single local transaction

## Layered architecture (per service)

Each service follows Clean Architecture / Hexagonal Architecture:

- business rules don't depend on Laravel, Eloquent, RabbitMQ, PostgreSQL
  or payment provider SDKs
- dependencies point inward, toward the domain and application layers
- external systems (persistence, message broker, payment providers,
  other APIs) are wired in through explicit ports and adapters

DDD gets applied where the business model actually justifies it, not as
a mandatory structure everywhere.

## Observability

Every service is expected to emit traces, metrics and logs through
OpenTelemetry. The collection/storage side (OpenTelemetry Collector,
Prometheus, Tempo, Loki, Grafana) is scaffolded under
`infrastructure/kubernetes/platform/observability/`, see
[`kubernetes.md`](kubernetes.md).

## Related docs

- [`kubernetes.md`](kubernetes.md) for the Kubernetes infra layer and how
  it maps to this architecture.
- [`event-catalog.md`](event-catalog.md) for every integration event,
  its producer, and its actual consumers.
- [`../adr/README.md`](../adr/README.md) for decisions not captured here.
