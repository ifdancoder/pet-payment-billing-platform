# Стратегия тестирования

*[English version](testing-strategy.md)*

Границы тестовых уровней определены в
[ADR 0004](../adr/0004-testing-strategy.ru.md).

| Уровень | Расположение | Граница |
| --- | --- | --- |
| Unit | `services/*/tests/Unit` | Domain behavior без инфраструктуры |
| Application | `services/*/tests/Application` | Use cases с test doubles |
| Integration | `services/*/tests/Integration` | Один сервис с реальными adapters |
| Feature | `services/*/tests/Feature` | Одно Laravel-приложение через HTTP или console |
| Contract | `services/*/tests/Contract` | Wire shape integration events |
| Component | `tests/component` | Один запущенный сервис с PostgreSQL/RabbitMQ и HTTP stubs |
| Service integration | `tests/integration` | Отдельная граница между запущенными сервисами |
| E2E | `tests/e2e` | Полные бизнес-сценарии |
| Resilience | `tests/resilience` | Сбои broker, workers и повторной доставки |
| Platform smoke | `tests/kind` | Ingress и поведение Kubernetes workloads |
| Load smoke | `tests/load` | Ограниченный k6-трафик по полному Compose-стеку |

## Suites репозитория

Component suites проверяют Subscription со stub-ами Customer/Catalog и
Notification со stub-ом Customer. Service integration suites покрывают каждую
границу цепочки от подписки до платежа. E2E suites проверяют успешную покупку,
первичный отказ платежа и неудачное продление. Resilience suites проверяют
повторную доставку, восстановление outbox, недоступность RabbitMQ и падение
consumer после commit, но до acknowledgement.

Каждый repository-level suite является отдельным Pest-проектом и Compose-стеком.
Команда `make test-compose` запускает их последовательно и удаляет стек вместе с
volumes после каждого suite.

## Правила тестов

- Поднимайте только сервисы, необходимые для проверяемой границы.
- Публикуйте seed event напрямую, если upstream producer находится за пределами
  границы и уже покрыт отдельным тестом.
- Проверяйте состояние другого сервиса через HTTP API, а не его базу.
- Используйте `eventually()` для положительных асинхронных проверок. Ограниченный
  фиксированный wait допустим только при проверке отсутствия эффекта.
- Используйте fake payment и email providers. Тесты не должны обращаться к live
  providers.
- Переносите orchestration helpers в `tests/support`, когда они используются
  больше чем одним suite.

Kind suite ожидает уже развёрнутый кластер. Он проверяет ingress routing, rolling
restart `billing-api` и один успешный сценарий подписки. Он не заменяет Compose
business suites.
