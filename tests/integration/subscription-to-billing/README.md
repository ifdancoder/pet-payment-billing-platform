# Service integration: Subscription to Billing

*[Русская версия](README.ru.md)*

Creates a subscription through HTTP, publishes `subscription.created.v1` through Subscription's outbox, and verifies that Billing opens an invoice.

- Customer and Catalog run as real synchronous dependencies.
- The final assertion polls Billing's HTTP API with `eventually()`.

## Run

```bash
cd tests/integration/subscription-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
