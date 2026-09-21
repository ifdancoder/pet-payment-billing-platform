# Resilience: consumer crash

*[English version](README.md)*

Убивает `billing-consumer` после commit базы и до AMQP acknowledgement, затем проверяет redelivery без второго invoice.

- `CONSUMER_CRASH_TEST_DELAY_MS` расширяет окно commit-to-ack только для этого suite; в других средах переменная не задана.
- Тест ждёт pre-ack log line consumer перед SIGKILL.
- Inbox deduplication обрабатывает redelivered event после restart.

## Запуск

```bash
cd tests/resilience/consumer-crash
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
