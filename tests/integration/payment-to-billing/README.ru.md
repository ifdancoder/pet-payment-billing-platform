# Service integration: Payment to Billing

*[English version](README.md)*

Создаёт инвойс через consumer Billing, позволяет Payment обработать его и проверяет, что Billing переводит инвойс в Paid и публикует `invoice.paid.v1`.

- Настоящая цепочка создаёт `invoice.created.v1` и `payment.succeeded.v1`.
- Проверка наблюдает Billing через HTTP и приватную AMQP test queue.

## Запуск

```bash
cd tests/integration/payment-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
