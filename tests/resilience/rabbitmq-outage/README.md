# Resilience: RabbitMQ outage

*[Русская версия](README.ru.md)*

Stops RabbitMQ, creates a subscription through HTTP, then restarts the broker and verifies that the outbox and consumer loops recover.

- The API write does not resolve an AMQP connection.
- The committed outbox row reaches Billing after the broker returns.

## Run

```bash
cd tests/resilience/rabbitmq-outage
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
