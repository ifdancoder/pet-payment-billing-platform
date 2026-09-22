# Component tests

*[Русская версия](README.ru.md)*

A component suite runs one service as a real process with PostgreSQL and RabbitMQ. WireMock replaces its synchronous HTTP dependencies.

- `subscription-service/`: Subscription API, outbox, and consumer with Customer/Catalog stubs.
- `notification-service/`: Notification consumer and delivery worker with a Customer stub.

See [the testing strategy](../../docs/architecture/testing-strategy.md) for the boundary between Feature, Component, and service integration tests.
