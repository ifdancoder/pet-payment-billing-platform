# Service integration: Payment to Billing

*[Русская версия](README.ru.md)*

Seeds an invoice through Billing's consumer, lets Payment process it, and verifies that Billing marks the invoice Paid and publishes `invoice.paid.v1`.

- The real chain produces `invoice.created.v1` and `payment.succeeded.v1`.
- The assertion observes Billing through HTTP and a private AMQP test queue.

## Run

```bash
cd tests/integration/payment-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
