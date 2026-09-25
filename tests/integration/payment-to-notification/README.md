# Service integration: Payment to Notification

*[Русская версия](README.ru.md)*

Publishes `payment.succeeded.v1` directly and verifies Notification's real consumer, Customer HTTP lookup, and delivery worker.

- A known customer receives one receipt.
- An unknown customer creates no notification. The negative assertion uses a bounded wait because `eventually()` cannot prove absence.

## Run

```bash
cd tests/integration/payment-to-notification
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
