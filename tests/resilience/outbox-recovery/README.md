# Resilience: outbox recovery

*[Русская версия](README.ru.md)*

Stops `billing-outbox`, creates an invoice and outbox row through Billing's consumer, then restarts the relay and observes the delayed event.

- The relay is stopped before the write, so publication cannot win a race.
- The test restarts the same container without replaying the source transaction.

## Run

```bash
cd tests/resilience/outbox-recovery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
