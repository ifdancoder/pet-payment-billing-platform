# Billing Platform

A billing and subscription platform built as a set of independently
deployable PHP/Laravel services.

This is a project for working through distributed billing systems in
practice: Clean Architecture, Hexagonal Architecture, DDD, event-driven
communication between services.

> Still early. Infrastructure and architecture first, application
> services haven't been built yet.

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

Planned services:

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

Where each Laravel service will live. Empty for now, nothing's been
built yet.

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

Current: Phase 0, repository and local infrastructure bootstrap.

Next: Phase 1, first application service and shared messaging
conventions.
