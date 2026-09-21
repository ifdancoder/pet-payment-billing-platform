# Service integration: Payment → Notification

*[English version](README.md)*

Пятый service integration test в тестовой пирамиде платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Сама граница уже реально проверяется E2E-сценарием
[`successful-subscription`](../../e2e/successful-subscription/), но
сфокусированный 2-сервисный тест всё равно намного быстрее локализует
падение именно здесь, чем прогон E2E из 16 контейнеров. Это не E2E:
здесь нет Identity, Catalog, Subscription, Billing, Payment.

## Почему здесь customer-service, а payment/billing — нет

`PaymentSucceededConsumer` синхронно ищет customer по HTTP, чтобы
получить email, прежде чем сможет отрендерить receipt — реальная
зависимость этой границы, а не сама граница, поэтому customer-service
в этом стеке. payment-service и billing-service — *нет*: то, что
Payment корректно публикует `payment.succeeded.v1`, уже покрыто
[`tests/integration/billing-to-payment/`](../billing-to-payment/),
поэтому этот тест публикует его напрямую вместо этого, заменяя собой
Outbox Payment — та же логика, что и во всех предыдущих срезах.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, отдельная логическая БД под каждый сервис |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован наружу, поскольку тест публикует напрямую |
| `customer-service` | синхронная зависимость notification (должен найти email клиента) |
| `notification-api` | отдаёт `GET /notifications` для проверок в тесте |
| `notification-ingest-consumer` | настоящий цикл `payment-succeeded:consume` — создаёт Pending Notification |
| `notification-delivery-worker` | настоящий цикл `notifications:deliver` — *другой* консьюмер, самой строки Notification, а не события RabbitMQ, переводит её в Sent |

## Запуск

```bash
cd tests/integration/payment-to-notification
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Два теста: happy path и guard, который должен доказать отрицание

Собственный guard `PaymentSucceededConsumer`: если поиск customer
ничего не находит (customer удалён, или customer-service недоступен),
ни Inbox, ни Notification не записываются — повторная доставка того же
события просто повторит поиск позже, вместо того чтобы консьюмер
записал состояние «failed», которое подавило бы легитимный retry.

Реально проверить это значит доказать отсутствие, а `eventually()`
(рассчитанный на ожидание, пока условие не станет *истинным*) для
этого не подходит. Второй тест публикует `payment.succeeded.v1` для
`customer_id`, который никогда не создавался, даёт неправильному
поведению реальное окно, чтобы проявиться (несколько итераций цикла
worker-а через фиксированный `sleep(3)`), затем проверяет, что список
notifications всё ещё пуст. Фиксированный sleep здесь — не тот
анти-паттерн, которого избегает весь остальной код в этом репозитории:
это единственный честный способ проверить «этого не происходит», в
отличие от преждевременного таймаута на «этого ещё не произошло».
