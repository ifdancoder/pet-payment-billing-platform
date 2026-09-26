# Architecture

*[Русская версия](overview.ru.md)*

The platform contains seven independently deployable Laravel services. Nginx
routes the local public API; Kubernetes uses ingress-nginx. Each service owns a
logical PostgreSQL database and is the only writer to that data.

| Service | Responsibility |
| --- | --- |
| identity-service | Accounts, merchants, memberships, API keys, and tokens |
| customer-service | Customers and billing contacts |
| catalog-service | Products and recurring prices |
| subscription-service | Subscription state and renewal scheduling |
| billing-service | Invoices and billing cycles |
| payment-service | Payment attempts and provider results |
| notification-service | Payment receipt notifications |

## Communication and consistency

Subscription and Notification perform synchronous HTTP lookups when they need
customer or price data. Cross-service state changes use RabbitMQ integration
events. [ADR 0002](../adr/0002-rabbitmq-messaging.md) defines the message
contract; [the event catalog](event-catalog.md) lists current producers and
consumers.

A command that changes service data and emits an event stores both changes in a
local transaction. Outbox workers publish committed events. Consumer handlers
record inbox entries in the same transaction as their local effects. RabbitMQ
delivery is at least once, so business uniqueness constraints remain necessary
where different events could produce the same result.

Application and domain code depend on ports. Eloquent, RabbitMQ, HTTP clients,
and provider adapters are implemented under Infrastructure. This boundary is
checked by the service architecture tests.

## Platform

The root Compose stack runs Nginx, PostgreSQL, RabbitMQ, all APIs, and their
workers. Kubernetes resources are split between application resources under
`infrastructure/kubernetes/base` and optional shared components under
`infrastructure/kubernetes/platform`. See [Kubernetes](kubernetes.md) for the
current deployment layout.

HTTP correlation IDs are generated or preserved by shared middleware and passed
to synchronous downstream calls. Event publishers include a correlation ID in
AMQP headers. The repository contains observability backend manifests, but the
applications do not yet export OpenTelemetry telemetry.
