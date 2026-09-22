# Component: subscription-service

*[English version](README.md)*

Запускает subscription-service с его базой, RabbitMQ, outbox relay, consumer и одним WireMock вместо customer-service и catalog-service.

- Успешное создание использует stubbed lookup-ы клиента и цены и публикует `subscription.created.v1`.
- Неизвестный customer приводит к 404.
- Напрямую опубликованный `invoice.paid.v1` активирует подписку без billing-service.

## Запуск

```bash
cd tests/component/subscription-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
