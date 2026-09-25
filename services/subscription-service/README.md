# subscription-service

*[Русская версия](README.ru.md)*

Owns subscriptions, renewal scheduling, and subscription state transitions. Customer and price validation use synchronous HTTP lookups.

## Interfaces

- HTTP: create, list, and fetch subscriptions; activate, cancel, or mark a subscription past due.
- Console workers: `subscription-events:consume`, `subscriptions:renew`, `outbox:publish`.
- Messaging: Consumes `invoice.paid.v1` and `invoice.payment_failed.v1`. Publishes subscription lifecycle and renewal events.

The public routes are documented in [the OpenAPI contract](../../docs/openapi/openapi.yaml).
The shared event envelope and delivery rules are defined in
[ADR 0002](../../docs/adr/0002-rabbitmq-messaging.md).

## Development

```bash
composer install
composer test -- --compact
```

Use the root `docker-compose.yaml` or Kubernetes manifests for cross-service
execution. This service expects its own logical PostgreSQL database.
