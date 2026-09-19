# Billing Platform

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
│   └── e2e/
├── docker-compose.yaml
└── Makefile
```

### services

All seven Laravel services. Each is independently runnable with its own
database; none are dockerized yet (see "Status").

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

Architecture notes and ADRs.

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
   (see [ADR 0001](docs/adr/0001-api-gateway-routing.md)); not yet
   reachable end-to-end since no service is dockerized.
2. RabbitMQ topology — messaging contract, naming, envelope and queue
   topology rules are written down
   ([ADR 0002](docs/adr/0002-rabbitmq-messaging.md) +
   [event catalog](docs/architecture/event-catalog.md)), formalizing
   what five services had already been doing by imitation. Retry/DLQ
   policy, publisher confirms and prefetch aren't built yet, and two
   real gaps are now tracked instead of invisible: catalog-service's
   Outbox never actually reaches RabbitMQ, and nothing consumes
   `payment.succeeded.v1`/`payment.failed.v1` in Billing or Subscription
   yet — the next concrete slice.
3. Distributed reliability — collect the Outbox/Inbox/idempotency
   patterns already used per-service into one set of platform rules, and
   actually test the crash scenarios (crash before ack, crash before
   outbox marked, duplicate delivery, broker/DB unavailable, provider
   timeout).
4. Observability — OpenTelemetry traces/metrics/logs, correlation IDs
   propagated through RabbitMQ headers, Grafana/Tempo/Prometheus/Loki.
5. Docker / local environment — Dockerfiles and `docker-compose.yaml`
   entries for all seven services, so the gateway from step 1 actually
   has something to route to.
6. Kubernetes — only once it's clear what's actually being deployed
   (each service is more than one workload: API + consumer + outbox
   worker, sometimes a CronJob).
7. CI/CD — per-service pipelines in a monorepo-aware build (lint,
   static analysis, test layers, build, scan, deploy, migrate, smoke
   test), only running for services that actually changed.
8. Contract + end-to-end testing — one E2E scenario exercising nearly
   the whole platform: Merchant → API Key → Customer → Product/Price →
   Subscription → Invoice → Payment → Notification.
9. Security hardening.
10. Load / failure testing.
