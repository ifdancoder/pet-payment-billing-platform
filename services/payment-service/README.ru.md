# payment-service

*[English version](README.md)*

Владеет платежами и попытками провайдера. Текущий provider adapter является детерминированным fake для локальной среды и тестов.

## Интерфейсы

- HTTP: список и получение платежей.
- Console workers: `invoice-created:consume`, `outbox:publish`.
- Messaging: Потребляет `invoice.created.v1`. Публикует `payment.succeeded.v1` и `payment.failed.v1`.

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
