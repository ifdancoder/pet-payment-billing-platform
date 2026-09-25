# Resilience: duplicate delivery

*[English version](README.md)*

Дважды публикует `subscription.created.v1` с одним event ID и проверяет, что Billing создаёт один invoice.

- RabbitMQ доставляет оба сообщения; inbox Billing подавляет второй эффект.
- Ограниченный wait используется, чтобы доказать отсутствие второго invoice.

## Запуск

```bash
cd tests/resilience/duplicate-delivery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
