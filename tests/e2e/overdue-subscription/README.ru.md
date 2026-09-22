# E2E: overdue subscription

*[English version](README.md)*

Завершает начальный платёж, вызывает `subscriptions:renew --as-of` после границы периода и проверяет, что отказ продления переводит Active-подписку в PastDue.

- Сумма `77770000` успешна для `subscription_create` и отклоняется для `subscription_cycle`.
- Финальный запуск scheduler не ставит renewal для PastDue-подписки.
- Compose file включает стек successful-subscription вместо его копирования.

## Запуск

```bash
cd tests/e2e/overdue-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
