# Component: subscription-service

*[Русская версия](README.ru.md)*

Runs subscription-service with its database, RabbitMQ, outbox relay, consumer, and one WireMock instance for customer-service and catalog-service.

- Successful creation uses the stubbed customer and price lookups and publishes `subscription.created.v1`.
- An unknown customer returns 404.
- A directly published `invoice.paid.v1` activates the subscription without billing-service.

## Run

```bash
cd tests/component/subscription-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
