# payment-service

*[Русская версия](README.ru.md)*

Owns payments and provider attempts. The current provider adapter is a deterministic fake used by local and test environments.

## Interfaces

- HTTP: list and fetch payments.
- Console workers: `invoice-created:consume`, `outbox:publish`.
- Messaging: Consumes `invoice.created.v1`. Publishes `payment.succeeded.v1` and `payment.failed.v1`.

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
