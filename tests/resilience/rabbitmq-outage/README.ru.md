# Resilience: RabbitMQ outage

*[English version](README.md)*

Останавливает RabbitMQ, создаёт subscription через HTTP, затем запускает broker и проверяет восстановление outbox и consumer loops.

- API write не открывает AMQP connection.
- Зафиксированный outbox row достигает Billing после возврата broker.

## Запуск

```bash
cd tests/resilience/rabbitmq-outage
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
