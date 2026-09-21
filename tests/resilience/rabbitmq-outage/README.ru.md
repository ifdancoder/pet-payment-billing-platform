# Resilience: RabbitMQ outage

*[English version](README.md)*

Третий resilience-тест (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Не бизнес-сценарий и не граница — конкретное утверждение о поведении
при сбое, и архитектурное, а не про code path: переживает ли создание
Subscription полную недоступность RabbitMQ, и догоняет ли остальная
цепочка сама, как только брокер возвращается, без необходимости
что-либо повторять, replay-ить или терять.

## На чём на самом деле держится это утверждение

Create-флоу `subscription-api` — `CreateSubscriptionHandler` и всё, от
чего он зависит (репозиторий, два HTTP-gateway к customer/catalog-service,
порт Outbox) — вообще не резолвит `AMQPChannel`. `AppServiceProvider`
регистрирует его как ленивый Laravel-синглтон; только
`PublishOutboxMessagesCommand` и команды `*-events:consume` вообще
просят контейнер его выдать. Так что у HTTP-запроса, создающего
Subscription, изначально нет пути в код к RabbitMQ, на котором он мог
бы упасть — этот тест существует, чтобы подтвердить, что это реально
так во время выполнения, а не только по чтению кода.

Переиспользует точный стек и create-флоу
[`subscription-to-billing/`](../../integration/subscription-to-billing/)
— это новый вопрос про существующую границу (переживает ли она outage),
а не новая граница.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс |
| `rabbitmq` | то, что проверяется — останавливается и перезапускается самим тестом, а не тем, что `docker compose up` решил его не стартовать |
| `customer-service` | синхронная зависимость subscription-api |
| `catalog-service` | синхронная зависимость subscription-api |
| `subscription-api` | запись, которую outage не должен затронуть |
| `subscription-outbox` | должен сам восстановить своё соединение, как только RabbitMQ вернётся, без посторонней помощи |
| `billing-api` | отдаёт `GET /invoices` для проверок в тесте |
| `billing-consumer` | тоже должен сам восстановить своё соединение, как только RabbitMQ вернётся |

## Запуск

```bash
cd tests/resilience/rabbitmq-outage
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Переиспользованный `DockerCompose`, вторая копия

[`tests/Support/DockerCompose.php`](tests/Support/DockerCompose.php) —
вторая копия того, что был написан для
[`outbox-recovery/`](../outbox-recovery/) — по дисциплине extraction,
используемой во всей истории `tests/support/` (копируем для первых
двух вызывающих, делимся на третьем), следующий resilience-тест,
которому понадобится управление start/stop, должен перенести это в
[`tests/support/`](../../support/), а не копировать в третий раз.

## Проверено вживую: реальные сбои соединения, а не удачное окно гонки

И `subscription-outbox`, и `billing-consumer` залогировали настоящие
ошибки `stream_socket_client(): Unable to connect to tcp://rabbitmq:5672
(Connection refused)` — по несколько штук каждый, из собственных
независимых циклов повтора — пока `rabbitmq` реально был недоступен, а
не тест, который случайно успел завершиться до следующей попытки
какого-то из worker-ов. Как только брокер вернулся, оба сразу же
преуспели на своей самой первой следующей итерации цикла:
`subscription-outbox` залогировал `Published 1 outbox message(s).`,
`billing-consumer` залогировал `Consumed 1 message(s).` — именно ту
строку, что ждала своей очереди.
