# Service integration: Billing to Subscription

*[Русская версия](README.ru.md)*

Verifies the Billing-to-Subscription result events with real services and RabbitMQ.

- `invoice.paid.v1` activates a Pending subscription.
- `invoice.payment_failed.v1` moves an Active subscription to PastDue and leaves a Pending subscription unchanged.

## Run

```bash
cd tests/integration/billing-to-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
