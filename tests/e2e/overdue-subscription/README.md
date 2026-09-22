# E2E: overdue subscription

*[Русская версия](README.ru.md)*

Completes the initial payment, invokes `subscriptions:renew --as-of` past the period boundary, and verifies that the renewal decline moves the Active subscription to PastDue.

- Amount `77770000` succeeds for `subscription_create` and declines for `subscription_cycle`.
- A final scheduler run queues no renewal for the PastDue subscription.
- The Compose file includes the successful-subscription stack instead of duplicating it.

## Run

```bash
cd tests/e2e/overdue-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
