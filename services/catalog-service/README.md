# catalog-service

*[Русская версия](README.ru.md)*

Owns products and recurring prices.

## Interfaces

- HTTP: create, list, update, and archive products; create, fetch, activate, and deactivate prices.
- Console workers: `outbox:publish`.
- Messaging: Publishes product and price lifecycle events. No service currently consumes them.

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
