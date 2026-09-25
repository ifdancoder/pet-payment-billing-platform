# subscription-service

*[English version](README.md)*

Владеет подписками, планированием продлений и переходами состояния. Клиента и цену проверяет синхронными HTTP-запросами.

## Интерфейсы

- HTTP: создание, список и получение подписок; активация, отмена и перевод в PastDue.
- Console workers: `subscription-events:consume`, `subscriptions:renew`, `outbox:publish`.
- Messaging: Потребляет `invoice.paid.v1` и `invoice.payment_failed.v1`. Публикует события жизненного цикла и продления подписки.

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
