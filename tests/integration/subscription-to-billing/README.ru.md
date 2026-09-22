# Service integration: Subscription to Billing

*[English version](README.md)*

Создаёт подписку через HTTP, публикует `subscription.created.v1` через outbox Subscription и проверяет, что Billing открыл инвойс.

- Customer и Catalog работают как реальные синхронные dependencies.
- Финальная проверка опрашивает HTTP API Billing через `eventually()`.

## Запуск

```bash
cd tests/integration/subscription-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
