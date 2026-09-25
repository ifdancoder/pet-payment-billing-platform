# billing-service

*[English version](README.md)*

Владеет инвойсами и биллинговыми циклами. Потребляет события подписок и платежей, затем публикует события жизненного цикла инвойса.

## Интерфейсы

- HTTP: список и получение инвойсов; аннулирование открытого инвойса.
- Console workers: `billing-events:consume`, `outbox:publish`.
- Messaging: Потребляет `subscription.created.v1`, `subscription.renewal_due.v1`, `payment.succeeded.v1` и `payment.failed.v1`. Публикует `invoice.created.v1`, `invoice.paid.v1`, `invoice.payment_failed.v1` и `invoice.voided.v1`.

Публичные маршруты описаны в [OpenAPI-контракте](../../docs/openapi/openapi.yaml).
Общий envelope событий и правила доставки определены в
[ADR 0002](../../docs/adr/0002-rabbitmq-messaging.ru.md).

## Разработка

```bash
composer install
composer test -- --compact
```

Для межсервисного запуска используйте корневой `docker-compose.yaml` или
Kubernetes-манифесты. Сервис ожидает отдельную логическую базу PostgreSQL.
