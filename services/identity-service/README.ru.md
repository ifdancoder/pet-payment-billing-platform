# identity-service

*[English version](README.md)*

Владеет пользователями, мерчантами, memberships, API keys и токенами аутентификации.

## Интерфейсы

- HTTP: регистрация, login, refresh и logout; управление мерчантами, memberships и API keys.
- Console workers: нет.
- Messaging: Сервис не публикует и не потребляет события RabbitMQ.

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
