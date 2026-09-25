# Service integration: Billing to Payment

*[Русская версия](README.ru.md)*

Publishes `subscription.created.v1` as the seed event, then verifies Billing's real outbox and Payment's real consumer create a succeeded payment.

- Subscription is outside this boundary; its publisher has separate coverage.
- The test declares the exchange and bindings before publishing to avoid a startup race.

## Run

```bash
cd tests/integration/billing-to-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
