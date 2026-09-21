# Billing Platform

*[Русская версия](README.ru.md)*

A billing and subscription platform built as a set of independently
deployable PHP/Laravel services.

This is a project for working through distributed billing systems in
practice: Clean Architecture, Hexagonal Architecture, DDD, event-driven
communication between services.

> All seven services are built. Now turning them into a real
> production-style platform: gateway, messaging topology, reliability,
> observability, Docker, Kubernetes, CI/CD, then end-to-end/load
> testing. See "Status" below.

## Goals

What the platform is meant to do:

- customer management
- product and price catalogs
- subscriptions and recurring billing
- invoice generation
- payment processing and refunds
- payment provider integrations
- async notifications

And the reliability side that comes with any distributed system:

- each service owns its own data
- eventual consistency
- idempotency (keys, idempotent consumers/inbox)
- transactional outbox
- retries and dead-letter queues
- distributed tracing and observability

## Architecture

Microservices, each one an independently executable Laravel app with its
own database.

Services:

| Service | Responsibility |
| --- | --- |
| Identity Service | Merchant authentication and authorization |
| Customer Service | Customers and billing profiles |
| Catalog Service | Products, prices and discounts |
| Subscription Service | Subscription lifecycle |
| Billing Service | Invoices and billing cycles |
| Payment Service | Payments, refunds and payment providers |
| Notification Service | Asynchronous customer notifications |

Services talk to each other over HTTP when they need an answer right
away, and through RabbitMQ events for everything else (most cross-service
workflows).

## Repository structure

```text
pet-payment-billing-platform/
├── services/
├── packages/
├── infrastructure/
│   ├── nginx/
│   ├── postgres/
│   ├── rabbitmq/
│   └── kubernetes/
│       ├── base/
│       └── platform/
├── docs/
│   ├── architecture/
│   └── adr/
├── scripts/
├── tests/
│   ├── integration/
│   ├── e2e/
│   └── resilience/
├── docker-compose.yaml
└── Makefile
```

### services

All seven Laravel services. Each is independently runnable with its own
database. Each also has a Dockerfile now and runs in the `kind` cluster
under `infrastructure/kubernetes/`; none are wired into the local
`docker-compose.yaml` yet (see "Status").

### packages

Shared technical contracts only, things like protocol definitions or
client SDKs. Domain models stay inside each service, never shared.

### infrastructure

Local and platform-level infra config.

Local dev (docker-compose) runs Nginx, PostgreSQL and RabbitMQ.

`infrastructure/kubernetes/` is a separate, cluster-facing layer:

- `base/` sets up the namespace and other cluster-wide bits
- `platform/` covers ingress, RabbitMQ, KEDA, External Secrets and the
  observability stack (OpenTelemetry Collector, Prometheus, Grafana,
  Tempo, Loki). Details in `infrastructure/kubernetes/platform/README.md`.

None of this is wired into `make up` yet. It's a separate track from
local dev.

### docs

Architecture notes and ADRs. English is canonical; where a Russian
translation exists it sits alongside as `*.ru.md`.

### tests

Cross-service tests that don't belong to any single service — see
[`docs/architecture/testing-strategy.md`](docs/architecture/testing-strategy.md)
for the full pyramid (each service's own `tests/Unit`, `Integration`
and `Feature` cover everything below this level):

- `integration/` — 2-3 real services through a real RabbitMQ, each its
  own Docker Compose stack + standalone Pest project.
- `e2e/` — full business flows across every service, fake
  payment/email providers.
- `resilience/` — failure-mode scenarios (duplicate delivery, consumer
  crash, broker outage), not business scenarios.

## Principles

### Service ownership

Each service owns its logic and its data. No service reaches into
another service's database directly.

### Database per service

Every service's data is logically isolated, even though locally they
might all sit on the same PostgreSQL instance for convenience. Ownership
stays separate no matter where the bytes physically live.

### Clean Architecture

Business rules don't know about Laravel, Eloquent, RabbitMQ, PostgreSQL
or payment providers. Dependencies point inward, toward the domain.

### Hexagonal Architecture

Anything external (databases, payment providers, message brokers, other
APIs) goes through ports and adapters, not called directly from business
logic.

### Domain-Driven Design

Used where it actually pays off, not forced onto every corner of the
system.

### Event-driven communication

Services announce state changes as events over RabbitMQ instead of
calling each other directly.

## Local development

### Requirements

- Docker
- Docker Compose
- GNU Make

### Setup

Copy the env file:

```bash
make init
```

Start everything:

```bash
make up
```

See what's running:

```bash
make ps
```

Ping the gateway:

```bash
make health
```

Stop everything:

```bash
make down
```

Remove containers and local volumes:

```bash
make clean
```

## Local endpoints

| Component | Address |
| --- | --- |
| API Gateway | `http://localhost:8080` |
| Gateway Health | `http://localhost:8080/health` |
| PostgreSQL | `localhost:5432` |
| RabbitMQ | `localhost:5672` |
| RabbitMQ Management | `http://localhost:15672` |

Credentials live in `.env`.

The gateway's public routing table (`/v1/...` → each service, see
[ADR 0001](docs/adr/0001-api-gateway-routing.md)) isn't reachable
through `make up` yet — it routes correctly, but none of the seven
services are containerized and added to `docker-compose.yaml` yet
(step 5 of the roadmap above).

## Technology roadmap

Initial infrastructure:

- Docker Compose
- Nginx
- PostgreSQL
- RabbitMQ

Application stack:

- PHP
- Laravel
- PostgreSQL
- Redis
- RabbitMQ

Architecture:

- Clean Architecture
- Hexagonal Architecture
- Domain-Driven Design
- Event-Driven Architecture

Reliability:

- Transactional Outbox
- Inbox / Idempotent Consumer
- Idempotency Keys
- Retry policies
- Dead Letter Queues
- Saga / Process Manager

Observability:

- OpenTelemetry
- Prometheus
- Grafana
- Tempo
- Loki

Deployment:

- Docker
- CI/CD
- Kubernetes

## Status

Actively in progress.

All seven services exist (Identity, Customer, Catalog, Subscription,
Billing, Payment, Notification), each a fully working Clean/Hexagonal
Laravel app with its own tests. No new services are planned — the
business decomposition is done. What's left is turning these seven
Laravel apps into an actual production-style platform, roughly in this
order:

1. **API Gateway / Ingress** — done for the routing/auth-boundary design
   (see [ADR 0001](docs/adr/0001-api-gateway-routing.md)) and reachable
   end-to-end in the `kind` cluster (Ingress → gateway → each service);
   still not reachable through local `docker-compose` (`make up`) since
   no service is wired into that compose file yet (step 5).
2. RabbitMQ topology — messaging contract, naming, envelope and queue
   topology rules are written down
   ([ADR 0002](docs/adr/0002-rabbitmq-messaging.md) +
   [event catalog](docs/architecture/event-catalog.md)), formalizing
   what five services had already been doing by imitation. The
   platform's core event chain is now wired end to end (Subscription →
   Billing → Payment → Billing/Subscription/Notification), including
   Billing translating payment outcomes into `subscription_id`-bearing
   events for Subscription to consume, and catalog-service's Outbox
   actually reaching RabbitMQ (was stuck on a log-only publisher).
   Retry/DLQ policy, publisher confirms and prefetch aren't built yet.
3. Distributed reliability — collect the Outbox/Inbox/idempotency
   patterns already used per-service into one set of platform rules, and
   actually test the crash scenarios (crash before ack, crash before
   outbox marked, duplicate delivery, broker/DB unavailable, provider
   timeout).
4. Observability — OpenTelemetry traces/metrics/logs, correlation IDs
   propagated through RabbitMQ headers, Grafana/Tempo/Prometheus/Loki.
5. Docker / local environment — Dockerfiles done for all seven
   services; `docker-compose.yaml` entries (so `make up` actually has
   something for the gateway to route to) aren't written yet. The two
   test-only compose stacks under `tests/integration/*/docker-compose.yaml`
   aren't a substitute — they exist to run one test suite, not for
   day-to-day local dev.
6. Kubernetes — done for the core RabbitMQ vertical slice: all seven
   services run in a local `kind` cluster (`infrastructure/kubernetes/`),
   each split into the right workloads (API Deployment, plus a Consumer
   and/or Outbox Deployment for whichever have messaging roles — not
   one Pod per service), with health probes, PodDisruptionBudgets and
   topology spread. NetworkPolicy and HPA/KEDA manifests exist but
   aren't applied locally (`kind`'s CNI doesn't enforce NetworkPolicy,
   and there's no metrics-server) — see
   `infrastructure/kubernetes/platform/README.md`.
7. CI/CD — per-service pipelines in a monorepo-aware build (lint,
   static analysis, test layers, build, scan, deploy, migrate, smoke
   test), only running for services that actually changed.
8. Contract + end-to-end testing — a full pyramid, not one big E2E
   suite: Unit/Application/Integration per service (exists already),
   plus new Component, Contract, Service integration, E2E and
   Resilience layers built one vertical slice at a time, same as the
   RabbitMQ chain itself was
   ([ADR 0004](docs/adr/0004-testing-strategy.md) +
   [testing strategy](docs/architecture/testing-strategy.md)). All five
   service integration slices for the platform's core event chain done
   (real Postgres, real RabbitMQ, no mocking): `subscription-to-billing`,
   `billing-to-payment`, `payment-to-billing`, `billing-to-subscription`
   and `payment-to-notification`, in
   [`tests/integration/`](tests/integration/). Plus the first full E2E
   scenario, [`successful-subscription`](tests/e2e/successful-subscription/):
   all seven services, real infra, no shortcuts — Merchant → Customer →
   Product/Price → Subscription → Invoice → Payment → Subscription
   Active → Notification, walked entirely through real HTTP. Plus three
   resilience tests:
   [`duplicate-delivery`](tests/resilience/duplicate-delivery/) (the
   same `event_id` published twice, proving Billing's Inbox actually
   stops the second one from creating a duplicate Invoice — verified
   live that the consumer genuinely processed both deliveries, not that
   a race just meant the second one never arrived),
   [`outbox-recovery`](tests/resilience/outbox-recovery/) (the test
   stops `billing-outbox` itself mid-scenario, creates an Invoice while
   it's down, then proves the missed row reaches the wire once it's
   running again) and
   [`rabbitmq-outage`](tests/resilience/rabbitmq-outage/) (the test
   stops the broker itself; proves creating a Subscription isn't
   affected at all, then proves both the outbox relay and the consumer
   recover their own connections once RabbitMQ is back — verified live
   via genuine `Connection refused` errors in both workers' own logs
   while it was down).
9. Security hardening.
10. Load / failure testing.
