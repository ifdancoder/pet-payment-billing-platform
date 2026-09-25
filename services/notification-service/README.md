# notification-service

*[Русская версия](README.ru.md)*

Creates and delivers payment receipt notifications. Customer contact details are resolved from customer-service over HTTP.

## Interfaces

- HTTP: list and fetch notifications.
- Console workers: `payment-succeeded:consume`, `notifications:deliver`.
- Messaging: Consumes `payment.succeeded.v1`. It publishes no integration events.

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
