# Service integration: Billing to Subscription

*[English version](README.md)*

Проверяет события результата Billing-to-Subscription с реальными сервисами и RabbitMQ.

- `invoice.paid.v1` активирует Pending-подписку.
- `invoice.payment_failed.v1` переводит Active-подписку в PastDue и не меняет Pending-подписку.

## Запуск

```bash
cd tests/integration/billing-to-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
