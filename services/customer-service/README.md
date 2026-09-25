# customer-service

*[Русская версия](README.ru.md)*

Owns customer records and billing contact details.

## Interfaces

- HTTP: create, list, fetch, update, and delete customers.
- Console workers: `outbox:publish`.
- Messaging: Publishes `customer.created.v1`. No service currently consumes it.

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
