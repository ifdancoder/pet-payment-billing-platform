# Component: notification-service

*[English version](README.md)*

Запускает notification-service с его базой, RabbitMQ, ingest consumer, delivery worker и WireMock вместо customer-service.

- Напрямую опубликованный `payment.succeeded.v1` получает адресата через WireMock и доставляет receipt.
- При 404 от customer lookup не создаются inbox row и notification, поэтому redelivery может повторить lookup.

## Запуск

```bash
cd tests/component/notification-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
