# 4. Стратегия тестирования

*[English version](0004-testing-strategy.md)*

## Статус

Accepted

## Контекст

Тесты отдельного сервиса не доказывают совместную работу outbox, RabbitMQ, inbox
и downstream state changes. Full-platform tests слишком дороги и широки, чтобы
заменить точечные тесты domain, adapters и границ.

## Решение

Тесты разделяются по runtime boundary:

| Уровень | Граница |
| --- | --- |
| Unit | Domain entity или value object без инфраструктуры |
| Application | Handler или use case с test doubles |
| Integration | Один adapter с реальной инфраструктурой |
| Feature | Одно Laravel-приложение через HTTP или console |
| Architecture | Dependency rules внутри одного сервиса |
| Contract | Wire shape producer integration event |
| Component | Один запущенный сервис с реальными database/broker и stubbed HTTP dependencies |
| Service integration | Отдельная граница между запущенными сервисами |
| E2E | Полный бизнес-сценарий через все необходимые сервисы |
| Resilience | Один crash, outage, retry или redelivery condition |
| Platform smoke | Kubernetes routing и поведение workloads |

Repository-level suites являются независимыми Pest-проектами с одноразовыми
Compose-стеками. Положительные асинхронные проверки используют polling через
`eventually()`. Состояние другого сервиса читается через public HTTP API, а не
его базу. Внешние payment и email providers заменяются детерминированными fakes.
Kubernetes tests проверяют платформу, Compose tests проверяют бизнес-поведение.

CI запускает service tests, documentation и Compose contract checks, все Compose
suites и ограниченный load smoke для pull requests и `main`. Kind suite
запускается отдельно против существующего кластера.

## Последствия

Сбой можно локализовать до domain rule, adapter, service boundary, workflow или
failure mode. Repository-level suites требуют больше времени, поэтому каждый из
них поднимает только нужные для границы сервисы. Общий orchestration code
переносится в `tests/support`, когда нужен более чем одному suite.
