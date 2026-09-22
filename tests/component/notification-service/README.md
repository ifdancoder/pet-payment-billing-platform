# Component: notification-service

*[Русская версия](README.ru.md)*

Runs notification-service with its database, RabbitMQ, ingest consumer, delivery worker, and a WireMock customer-service.

- A directly published `payment.succeeded.v1` resolves the recipient through WireMock and delivers a receipt.
- A 404 customer lookup creates neither an inbox record nor a notification, allowing redelivery to retry the lookup.

## Run

```bash
cd tests/component/notification-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
