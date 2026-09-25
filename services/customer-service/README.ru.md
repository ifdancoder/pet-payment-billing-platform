# customer-service

*[English version](README.md)*

Владеет записями клиентов и платёжными контактами.

## Интерфейсы

- HTTP: создание, список, получение, изменение и удаление клиентов.
- Console workers: `outbox:publish`.
- Messaging: Публикует `customer.created.v1`. Сейчас это событие не потребляет ни один сервис.

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
