# E2E: неудачный платёж

*[English version](README.md)*

Второй полный end-to-end сценарий платформы, и первый failure-путь (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Те же семь настоящих сервисов, та же цепочка исключительно через
реальный HTTP, что и в
[`successful-subscription/`](../successful-subscription/) — единственное
отличие в том, что сумма Price выставлена в
`FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS` (`66660000`,
любая валюта), так что списание гарантированно отклоняется, а не
проходит.

## Почему понадобилась сумма-триггер, а не поле-триггер

`ChargeRequest`, boundary DTO, которое принимает
`IPaymentGatewayPort::charge()`, несёт только id попытки и сумму —
никакого понятия карты/токена, которое тест мог бы выставить в «всегда
отклонять». Добавление такого поля означало бы протащить концепцию
«как это должно провалиться» через Subscription и Billing тоже, просто
чтобы оно дошло до шлюза тремя хопами ниже, ради чисто тестовой цели.
Сумма же, наоборот, уже и есть то единственное значение, которое
`POST .../prices` этого сценария выставляет и которое доходит
абсолютно неизменным до самого `ChargeRequest`, который строит
`ProcessPaymentHandler` — поэтому
[`FakePaymentGateway`](../../../services/payment-service/app/Infrastructure/Payment/Adapters/PaymentGateway/Fake/FakePaymentGateway.php)
отклоняет одну конкретную, намеренно безошибочную сумму (`66660000`
minor units — $666 600.00 в любой валюте) вместо этого. Ни одна
реальная цена никогда случайно на неё не попадёт, и это не потребовало
никаких изменений за пределами самого `FakePaymentGateway`.

## Что это доказывает

Не новая граница событий — каждый хоп здесь (`subscription.created.v1`,
`invoice.created.v1`, `payment.failed.v1`, `invoice.payment_failed.v1`)
уже покрыт собственным срезом [`tests/integration/`](../../integration/).
Что может показать только полный E2E-прогон — это где вся цепочка
*реально* оставляет вещи после настоящего decline, произведённого
реальной границей шлюза, а не прямой публикацией-заменителем:

- Invoice остаётся **Open** — `MarkInvoicePaidHandler` просто никогда
  не достигается, поскольку `payment.succeeded.v1` никогда не
  появляется, чтобы его вызвать.
- Сам Payment становится **Failed**, с `attempts[0].failure_code`
  равным `card_declined` — тот факт, который увидел бы реальный
  вызывающий этого API.
- Subscription — который так и не дошёл до Active — остаётся
  **Pending**, а не PastDue. Собственный guard
  `HandleInvoicePaymentFailedHandler` (уже доказанный отдельно в
  [`tests/integration/billing-to-subscription/`](../../integration/billing-to-subscription/))
  переводит только Active → PastDue; самый первый неудачный платёж до
  активации — не этот случай.
- Notification вообще ничего не производит для этого клиента — он
  консьюмит только `payment.succeeded.v1`, которое этот сценарий
  никогда не производит.

## Как доказать, что это отсутствия, а не «ещё не произошло»

`eventually()` ждёт, пока условие не станет истинным; он не может
подтвердить, что оно остаётся ложным. Как только Payment подтверждён
Failed, тест не может опросом доказать, что Invoice и Subscription
никогда не изменятся — поэтому, то же самое задокументированное
исключение, что и в
[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/)
и
[`tests/resilience/consumer-crash/`](../../resilience/consumer-crash/),
даёт нижестоящей реакции (`payment.failed.v1` → `billing-consumer`
релеит `invoice.payment_failed.v1` → guard `subscription-consumer`)
реальное, фиксированное окно — несколько настоящих итераций цикла
worker-а — прежде чем проверить, что оба остались ровно там, где и
были.

## Запуск

```bash
cd tests/e2e/failed-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Проверено вживую

`billing-consumer` за этот прогон обработал ровно 2 сообщения
(`subscription.created.v1`, затем `payment.failed.v1`) — собственные
логи `payment-outbox` показывают ровно 1 опубликованное сообщение
(`payment.failed.v1`), а `subscription-consumer` обработал ровно 1
(`invoice.payment_failed.v1`). `notification-ingest-consumer` обработал
**0** — прямое подтверждение, что он ничего не увидел, а не просто что
ничего не появилось в `GET /notifications`.
