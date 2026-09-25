# notification-service

*[English version](README.md)*

Создаёт и доставляет уведомления об оплате. Контакт клиента получает из customer-service по HTTP.

## Интерфейсы

- HTTP: список и получение уведомлений.
- Console workers: `payment-succeeded:consume`, `notifications:deliver`.
- Messaging: Потребляет `payment.succeeded.v1`. Интеграционные события не публикует.

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
