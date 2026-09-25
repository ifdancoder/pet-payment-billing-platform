# Resilience: duplicate delivery

*[Русская версия](README.ru.md)*

Publishes the same `subscription.created.v1` event ID twice and verifies that Billing creates one invoice.

- RabbitMQ delivers both messages; Billing's inbox suppresses the second effect.
- A bounded wait is used to prove that a second invoice does not appear.

## Run

```bash
cd tests/resilience/duplicate-delivery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
