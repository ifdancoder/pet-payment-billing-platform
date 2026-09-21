# Service integration: Payment → Billing

*[English version](README.md)*

Третий service integration test в тестовой пирамиде платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Идёт на один шаг дальше, чем
[`billing-to-payment/`](../billing-to-payment/): тот останавливается,
как только Payment завершился успехом, а этот проверяет, что Billing
реально консьюмит `payment.succeeded.v1`, помечает свой Invoice как
Paid и republish-ит `invoice.paid.v1` с восстановленным
`subscription_id` — ради чего этот hop трансляции вообще существует
(собственное событие Payment его никогда не несёт, см.
[ADR 0002](../../../docs/adr/0002-rabbitmq-messaging.md)). Это не E2E:
здесь нет Identity, Customer, Catalog, Subscription, Notification.

## Почему seed-событие, и почему остальное не симулируется

Чтобы пометить Invoice как Paid, он должен уже существовать в базе
billing — `MarkInvoicePaidHandler` ищет его по ID и падает, если его
нет. Подделать это снаружи через HTTP нечем. Поэтому тест сеет
единственный нужный Invoice, напрямую публикуя `subscription.created.v1`
(та же логика, что в `billing-to-payment/`: собственный Outbox
Subscription уже покрыт
[`subscription-to-billing/`](../subscription-to-billing/)), а дальше
даёт отработать *реальной* цепочке — `invoice.created.v1`, Payment и
`payment.succeeded.v1` производятся настоящим кодом
`billing-outbox`/`payment-consumer`/`payment-outbox`, а не
симулируются. Изначальный план был подделать `payment.succeeded.v1`
напрямую (полностью пропустив Payment), но тогда тесту пришлось бы
выдумывать `payment_id`, за которым не стоит ни одной настоящей записи
Payment, и вообще не проверялась бы реальная корреляция по `invoice_id`
— эта версия доказывает, что вся граница работает, когда система сама
генерирует и прокидывает свои ID, а не просто что консьюмер Billing
умеет распарсить корректно сформированное сообщение.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, отдельная логическая БД под каждый сервис |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован наружу, поскольку тест и публикует, и читает напрямую |
| `billing-api` | отдаёт `GET /invoices` для проверок в тесте |
| `billing-consumer` | настоящий цикл `billing-events:consume` — создаёт Invoice из seed-события, позже помечает его Paid |
| `billing-outbox` | настоящий Outbox relay — публикует `invoice.created.v1`, позже `invoice.paid.v1` |
| `payment-api` | отдаёт `GET /payments` для проверки в тесте |
| `payment-consumer` | настоящий цикл `invoice-created:consume` — автоматически создаёт и обрабатывает Payment |
| `payment-outbox` | настоящий Outbox relay — публикует `payment.succeeded.v1` |

## Запуск

```bash
cd tests/integration/payment-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Общая тестовая инфраструктура

Это третий срез, поэтому по правилу extraction из
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)
это первый срез, использующий [`tests/support/`](../../support/) —
небольшой локальный Composer-пакет (подключается через `path`-репозиторий),
предоставляющий `eventually()` и `AmqpTestClient` — обобщённый помощник
публикации/биндинга/чтения для exchange `billing.events` — вместо того
чтобы копировать `eventually.php` в третий раз. У
`subscription-to-billing/` и `billing-to-payment/` всё ещё свои
локальные копии; их миграция — последующая работа, не часть этого
среза.

`AmqpTestClient` — это то, что делает возможными часть с seed-событием
и часть с проверкой republish в этом тесте: он объявляет и биндит
`billing.events.v1` перед публикацией в неё (тот же fix гонки, что был
в `EventPublisher` из `billing-to-payment/`, только обобщённый), и
может забиндить приватную одноразовую очередь на любой routing key
(`bindTestQueue('invoice.paid.v1')`), чтобы проверить, что сервис
реально republish-нул что-то — а не просто что изменилось его
собственное HTTP-состояние.
