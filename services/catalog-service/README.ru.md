# catalog-service

*[English version](README.md)*

Владеет продуктами и recurring prices.

## Интерфейсы

- HTTP: создание, список, изменение и архивирование продуктов; создание, получение, активация и деактивация цен.
- Console workers: `outbox:publish`.
- Messaging: Публикует события жизненного цикла продуктов и цен. Сейчас их не потребляет ни один сервис.

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
