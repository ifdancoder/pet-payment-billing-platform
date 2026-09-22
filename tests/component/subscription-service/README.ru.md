# Component: subscription-service

*[English version](README.md)*

Первый Component-тест платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Ровно один реальный сервис, его собственный реальный Postgres и
реальный RabbitMQ, и stub-сервер, заменяющий обе его синхронные
HTTP-зависимости (customer-service, catalog-service) — никогда не
настоящие сервисы, в отличие от любого стека
[`tests/integration/`](../../integration/).

## Почему это другой вопрос, чем `subscription-to-billing`

[`tests/integration/subscription-to-billing/`](../../integration/subscription-to-billing/)
уже доказывает, что этот же create-флоу работает с *настоящими*
customer-service и catalog-service в цепочке, и что настоящий
billing-service реально потребляет `subscription.created.v1`. Этот
тест задаёт более узкий и дешёвый вопрос: корректен ли контейнер
`subscription-service` сам по себе — его HTTP API, его путь
Outbox/RabbitMQ publish, и его собственный путь RabbitMQ consume — без
затрат на поднятие двух и более дополнительных сервисов ради ответа на
это. Здесь же guard-условие, которое было бы медленно или неудобно
устроить против настоящего customer-service (конкретно — неизвестный
customer), становится тривиальным: один фиксированный stub-маппинг
вместо создания сломанного состояния в настоящем сервисе.

То, что `billing-service` корректно публикует
`invoice.paid.v1`/`invoice.payment_failed.v1`, уже покрыто
[`tests/integration/payment-to-billing/`](../../integration/payment-to-billing/),
поэтому consume-сторона теста здесь публикует напрямую — та же логика,
что и в любом срезе `tests/integration/` для вышестоящего события, не
являющегося предметом теста.

## Почему WireMock, а не самописный stub

Ответы `customer-service` и `catalog-service`, которые реально читает
subscription-service, — чистые lookup-ы: никакой бизнес-логики, просто
JSON, ключом к которому служит запрошенный id (см.
[`HttpCustomerGateway`](../../../services/subscription-service/app/Infrastructure/Subscription/Adapters/Gateways/HttpCustomerGateway.php)
и
[`HttpCatalogGateway`](../../../services/subscription-service/app/Infrastructure/Subscription/Adapters/Gateways/HttpCatalogGateway.php)).
Декларативные JSON-маппинги [WireMock](https://wiremock.org/)
(`wiremock/mappings/`) выражают это напрямую, без кода, который нужно
писать и поддерживать: `--global-response-templating` позволяет ответу
маппинга эхом вернуть сегмент пути из реального запроса
(`{{request.path.[3]}}`), так что stub отвечает на *любой*
customer/price id фиксированным customer/price, а не требует от теста
заранее знать каждый id. Один инстанс обслуживает оба API — оба
представляют собой просто «найти ресурс по id через HTTP» по разным
путям, так что маршрутизировать оба через один контейнер WireMock
проще, чем запускать два.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, одна база (`subscription`) — в этом стеке всего один сервис |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован, поскольку тест публикует/читает напрямую |
| `customer-catalog-stub` | WireMock, заменяющий и customer-service, и catalog-service |
| `subscription-api` | настоящий HTTP API — то, что проверяется |
| `subscription-outbox` | настоящий Outbox relay |
| `subscription-consumer` | настоящий цикл `subscription-events:consume`, реагирующий на `invoice.paid.v1`/`invoice.payment_failed.v1` |

## Запуск

```bash
cd tests/component/subscription-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Проверено вживую

`GET /__admin/requests` на контейнере WireMock показал настоящие
HTTP-запросы от собственного контейнера `subscription-api`
(`User-Agent: GuzzleHttp/8`), а не in-process fake — ответ, на который
опирается тест, реально прошёл через сеть туда и обратно. Собственные
логи `subscription-outbox` и `subscription-consumer` каждый показали
ровно по одной строке `Published 1`/`Consumed 1` за прогон теста,
соответствуя одному `subscription.created.v1` и одному
`invoice.paid.v1`, которые производит и потребляет сьют. Весь сьют из
трёх тестов прошёл меньше чем за две секунды — та самая экономия,
которую ADR 0004 предсказывал для Component по сравнению с Service
integration, за счёт необходимости всего в одном контейнере сервиса
вместо двух и более.
