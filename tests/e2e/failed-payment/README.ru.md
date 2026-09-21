# E2E: неудачный платёж

*[English version](README.md)*

Использует сумму цены `66660000`, зарезервированный decline trigger fake gateway. Настоящая цепочка оставляет invoice Open, payment Failed с `card_declined`, subscription Pending и не создаёт notification.

- Trigger использует существующее поле amount; test-only поле не проходит через границы сервисов.
- После отказа ограниченный wait даёт downstream workers время проявить ошибочный переход состояния.

## Запуск

```bash
cd tests/e2e/failed-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
