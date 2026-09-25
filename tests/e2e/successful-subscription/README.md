# E2E: successful subscription

*[Русская версия](README.ru.md)*

Creates a merchant, customer, product, price, and subscription through public HTTP. The event chain creates and pays the invoice, activates the subscription, and delivers a notification.

- All seven services run in the stack.
- The test does not publish events or edit service databases directly.

## Run

```bash
cd tests/e2e/successful-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
