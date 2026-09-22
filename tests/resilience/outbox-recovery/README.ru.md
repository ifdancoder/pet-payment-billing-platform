# Resilience: outbox recovery

*[English version](README.md)*

Останавливает `billing-outbox`, создаёт invoice и outbox row через consumer Billing, затем перезапускает relay и наблюдает отложенное событие.

- Relay останавливается до записи, поэтому публикация не может выиграть race.
- Тест перезапускает тот же контейнер без повторения исходной транзакции.

## Запуск

```bash
cd tests/resilience/outbox-recovery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
