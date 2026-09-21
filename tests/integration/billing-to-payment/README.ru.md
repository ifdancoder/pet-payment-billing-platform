# Service integration: Billing to Payment

*[English version](README.md)*

Публикует `subscription.created.v1` как seed event, затем проверяет, что настоящий outbox Billing и consumer Payment создают успешный платёж.

- Subscription находится за пределами этой границы; его publisher покрыт отдельно.
- Тест объявляет exchange и bindings до публикации, чтобы исключить startup race.

## Запуск

```bash
cd tests/integration/billing-to-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
