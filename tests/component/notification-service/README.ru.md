# Component: notification-service

*[English version](README.md)*

Второй Component-тест платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)),
та же форма, что и у
[`tests/component/subscription-service/`](../subscription-service/):
ровно один реальный сервис, его собственный реальный Postgres и
реальный RabbitMQ, и WireMock-stub, заменяющий его одну синхронную
HTTP-зависимость (customer-service) — никогда не настоящий сервис.

## Почему здесь нет publish-стороны для проверки

В отличие от `subscription-service`, `notification-service` ничего не
публикует в `billing.events` (см.
[каталог событий](../../../docs/architecture/event-catalog.ru.md)) —
нет ни Outbox, ни нижестоящего события для проверки на wire. Его
единственная задача здесь — правильно реагировать на напрямую
опубликованный `payment.succeeded.v1`, заменяющий собой
`payment-service`, которого в этом стеке вообще нет. То, что
`payment-service` реально публикует это событие, уже покрыто
[`tests/integration/billing-to-payment/`](../../integration/billing-to-payment/).

## Почему это другой вопрос, чем `payment-to-notification`

[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/)
уже доказывает guard «нет контакта — нет notification» у
`PaymentSucceededConsumer` против *настоящего* customer-service, создавая
связь merchant/customer, которая затем пропадает. Здесь тот же guard
ничего не стоит устроить: один фиксированный sentinel customer id, на
который stub всегда отвечает 404
(`wiremock/mappings/customer-not-found.json`), вместо создания
настоящего сломанного состояния. Delivery worker
(`notifications:deliver`) не требует собственного stub-а —
`FakeEmailSender` это in-process fake, а не HTTP-вызов — так что этот
тест заодно реально проверяет и этого worker-а, чего не делают ни
`tests/integration/payment-to-notification/`, ни unit-тест самого
consumer-а по отдельности.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, одна база (`notification`) |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован, поскольку тест публикует напрямую |
| `customer-stub` | WireMock, заменяющий customer-service |
| `notification-api` | настоящий HTTP API — отдаёт `GET /notifications` для проверок теста |
| `notification-ingest-consumer` | настоящий цикл `payment-succeeded:consume` — то, что проверяется |
| `notification-delivery-worker` | настоящий цикл `notifications:deliver`, использующий `FakeEmailSender` |

## Запуск

```bash
cd tests/component/notification-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Проверено вживую

Собственные логи `notification-ingest-consumer` показали ровно 2
строки `Consumed 1` — по одной на тест — а `notification-delivery-worker`
показал ровно одну `Delivered 1 notification(s).`, соответствующую
только notification из happy-path теста (тест с неизвестным customer
ничего не создаёт для доставки). `GET /__admin/requests` на контейнере
WireMock показал оба реальных lookup-а — sentinel 404-id и
happy-path customer id — подтверждая, что оба теста реально прошли
через сеть туда и обратно, а не попали в in-process fake.
