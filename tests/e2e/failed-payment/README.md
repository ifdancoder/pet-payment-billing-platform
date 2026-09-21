# E2E: failed payment

*[Русская версия](README.ru.md)*

Uses price amount `66660000`, the fake gateway's reserved decline trigger. The real event chain leaves the invoice Open, the payment Failed with `card_declined`, the subscription Pending, and creates no notification.

- The trigger reuses the existing amount field; no test-only field crosses service boundaries.
- After the payment fails, a bounded wait gives downstream workers time to expose an incorrect state transition.

## Run

```bash
cd tests/e2e/failed-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
