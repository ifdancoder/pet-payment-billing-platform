# identity-service

*[Русская версия](README.ru.md)*

Owns users, merchants, memberships, API keys, and authentication tokens.

## Interfaces

- HTTP: registration, login, token refresh and logout; merchant, membership, and API-key management.
- Console workers: none.
- Messaging: The service has no RabbitMQ publisher or consumer.

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
