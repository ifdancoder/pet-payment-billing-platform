# Resilience: consumer crash

*[Русская версия](README.ru.md)*

Kills `billing-consumer` after its database commit and before AMQP acknowledgement, then verifies redelivery without a duplicate invoice.

- `CONSUMER_CRASH_TEST_DELAY_MS` widens only the commit-to-ack window for this suite; it is unset elsewhere.
- The test waits for the consumer's pre-ack log line before sending SIGKILL.
- Inbox deduplication handles the redelivered event after restart.

## Run

```bash
cd tests/resilience/consumer-crash
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
