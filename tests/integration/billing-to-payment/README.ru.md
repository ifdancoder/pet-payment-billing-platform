# Service integration: Billing → Payment

*[English version](README.md)*

Второй service integration test в тестовой пирамиде платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Проверяет, как billing-api консьюмит `subscription.created.v1` и
открывает Invoice, затем публикует `invoice.created.v1` через реальный
Outbox в реальный RabbitMQ, а payment-api консьюмит его и автоматически
обрабатывает Payment. Это не E2E: здесь нет Identity, Customer,
Catalog, Subscription, Notification.

## Почему без subscription-service

Billing создаёт Invoice только консьюмя `subscription.created.v1` —
прямого HTTP-эндпоинта для этого нет. Вместо того чтобы тащить в этот
стек subscription-service (и, транзитивно, customer/catalog-service)
только ради одного этого события, тест публикует его напрямую в
exchange (см.
[`tests/Support/EventPublisher.php`](tests/Support/EventPublisher.php))
— ровно в том wire-формате, который производит `RabbitMqEventPublisher`.
То, что Subscription реально корректно публикует это событие, уже
покрыто
[`tests/integration/subscription-to-billing/`](../subscription-to-billing/)
— дублировать это покрытие здесь означало бы просто замедлить тест и
усложнить локализацию его падений.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, отдельная логическая БД под каждый сервис |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован наружу, поскольку тест публикует напрямую |
| `billing-api` | отдаёт `GET /invoices` для первой проверки в тесте |
| `billing-consumer` | настоящий цикл `billing-events:consume`, превращающий опубликованное событие в Invoice |
| `billing-outbox` | настоящий Outbox relay, публикующий `invoice.created.v1` |
| `payment-api` | отдаёт `GET /payments` для второй проверки в тесте |
| `payment-consumer` | настоящий цикл `invoice-created:consume`, автоматически создающий и обрабатывающий Payment |

## Запуск

```bash
cd tests/integration/billing-to-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Дублированный fix гонки — стоит знать

`EventPublisher` объявляет и биндит `billing.events.v1` (то же самое
имя очереди и тот же routing key, что использует
`ConsumeBillingEventsCommand`) перед публикацией. Без этого публикация
до первой итерации собственного цикла `billing-consumer` молча теряла
бы сообщение — topic exchange ещё некуда его роутить. Объявление одной
и той же очереди из двух мест безопасно (идемпотентно), и это было
осознанным решением — вместо того чтобы забивать тест фиксированной
задержкой на старте.
