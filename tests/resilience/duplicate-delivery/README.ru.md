# Resilience: duplicate delivery

*[English version](README.md)*

Первый resilience-тест платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Это не бизнес-сценарий, как [`tests/e2e/`](../../e2e/), и не граница,
как [`tests/integration/`](../../integration/) — конкретное
утверждение о поведении при сбое: действительно ли Inbox у Billing
останавливает повторно доставленный `subscription.created.v1` от
создания второго Invoice, или эта гарантия существует только в
докблоке.

## Почему именно это первым

At-least-once delivery означает, что RabbitMQ *реально* передоставит
сообщение, ack которого он не увидел — консьюмер, упавший после
коммита транзакции, но до ack, или неподтверждённое сообщение, у
которого просто истёк таймаут. Любой такой сбой производит одно и то
же на wire: один и тот же `event_id`, приходящий дважды. Этот тест не
воспроизводит сам сбой (это `tests/resilience/consumer-crash/`, ещё не
построен) — он напрямую воспроизводит то общее, что есть у любой формы
такого сбоя, публикуя один и тот же `event_id` дважды через параметр
`eventId` у
[`AmqpTestClient::publish()`](../../support/src/AmqpTestClient.php)
(добавлен именно ради этого теста — во всех остальных срезах он
генерируется автоматически).

Переиспользует ту же границу `billing-consumer`, которую уже проверяют
[`subscription-to-billing/`](../../integration/subscription-to-billing/)
и [`billing-to-payment/`](../../integration/billing-to-payment/) — это
не проверка новой границы, это новый вопрос про уже существующую.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс |
| `rabbitmq` | настоящий брокер — AMQP-порт опубликован наружу, поскольку тест публикует дубликат напрямую |
| `billing-api` | отдаёт `GET /invoices` для проверок в тесте |
| `billing-consumer` | настоящий цикл `billing-events:consume` — то, что проверяется |

## Запуск

```bash
cd tests/resilience/duplicate-delivery
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Проверено вживую: консьюмер реально обработал обе доставки

Проверено в собственных логах `billing-consumer`, а не выведено из
прохождения теста: он залогировал `Consumed 1 message(s).` дважды —
у RabbitMQ нет понятия «это то же самое событие, что и раньше», он
доставил оба сообщения ровно так, как они были опубликованы. При этом
Invoice всё равно один. Это guard `recordIfNew()` в Inbox реально
держит, а не RabbitMQ тихо делает дедупликацию за него.

## Ещё один `sleep()`, и почему

Тот же осознанный исключительный случай, что задокументирован в
[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/):
доказать «второй Invoice не появляется» значит доказать отсутствие, а
`eventually()` (рассчитанный на ожидание, пока условие не станет
*истинным*) для этого не подходит. Это даёт неправильному поведению
реальное окно — несколько настоящих итераций цикла `billing-consumer`
через фиксированный `sleep(3)` — прежде чем проверить финальное
состояние.
