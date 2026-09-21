# Service integration: Billing → Subscription

*[English version](README.md)*

Четвёртый service integration test в тестовой пирамиде платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Проверяет обработку `invoice.paid.v1` и `invoice.payment_failed.v1` в
subscription-consumer — включая guard-условия, которые кодируют их
обработчики, а не только happy path (см. собственные докблоки
`HandleInvoicePaidHandler` и `HandleInvoicePaymentFailedHandler` в
subscription-service). Это не E2E: здесь нет Identity, Payment,
Notification, и самого Billing тоже нет в этом стеке (см. ниже).
Customer и Catalog *есть* — не как граница под тестом, а потому что
создание Subscription вообще требует их.

## Почему этот срез отличается от остальных

В отличие от Invoice или Payment, у Subscription есть настоящий
HTTP-эндпоинт `POST /subscriptions` — не нужен трюк с прямой
публикацией, чтобы его посеять, нужен просто настоящий запрос через
настоящий create-флоу. Но этот флоу синхронно вызывает customer-service
и catalog-service (`HttpCustomerGateway`, `HttpCatalogGateway`),
поэтому оба они в этом стеке, хотя ни один из них не является границей,
которую проверяет этот тест — та же логика, что уже установил
`subscription-to-billing/` для их включения.

billing-service *не* в этом стеке: то, что он корректно публикует
`invoice.paid.v1`/`invoice.payment_failed.v1`, уже покрыто
[`tests/integration/payment-to-billing/`](../payment-to-billing/),
поэтому этот тест публикует их напрямую (см.
[`BillingToSubscriptionTest.php`](tests/BillingToSubscriptionTest.php)),
заменяя собой Outbox Billing.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, отдельная логическая БД под каждый сервис |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован наружу, поскольку тест публикует напрямую |
| `customer-service` | синхронная зависимость subscription-api (должен найти customer) |
| `catalog-service` | синхронная зависимость subscription-api (должен найти price) |
| `subscription-api` | настоящий create-флоу, плюс `GET /subscriptions/{id}` для проверок в тесте |
| `subscription-consumer` | настоящий цикл `subscription-events:consume`, превращающий оба опубликованных события в переходы состояния |

## Запуск

```bash
cd tests/integration/billing-to-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Два теста, не один — включая guard-условие

`HandleInvoicePaymentFailedHandler` переводит только `Active` →
`PastDue`; самый первый неудачный платёж Pending-подписки (до того, как
она хоть раз активировалась) остаётся Pending. Чтобы реально проверить
этот guard, нужна подписка, которая *действительно* Active, а не просто
какая-то существующая — поэтому второй тест сначала публикует
`invoice.paid.v1` и ждёт `active`, прежде чем опубликовать
`invoice.payment_failed.v1` и проверить `past_due`. Проверка только
happy path здесь упустила бы, действительно ли это guard-условие — не
сам переход, а именно условие — работает, когда его запускает реальное,
асинхронное, повторно доставляемое событие, а не прямой вызов метода в
Application-тесте.
