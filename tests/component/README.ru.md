# Component тесты

*[English version](README.md)*

Component suite запускает один сервис как реальный процесс с PostgreSQL и RabbitMQ. Его синхронные HTTP dependencies заменяет WireMock.

- `subscription-service/`: Subscription API, outbox и consumer со stub-ами Customer/Catalog.
- `notification-service/`: Notification consumer и delivery worker со stub-ом Customer.

Границы Feature, Component и service integration tests описаны в [стратегии тестирования](../../docs/architecture/testing-strategy.ru.md).
