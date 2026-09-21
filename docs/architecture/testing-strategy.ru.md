# Стратегия тестирования

*[English version](testing-strategy.md)*

Живая версия решения из [ADR 0004](../adr/0004-testing-strategy.ru.md)
— что реально построено сегодня, и что дальше. Собрана по факту кода и
дерева `tests/`, а не по целевому дизайну; обновляйте этот файл по мере
появления новых срезов, так же, как
[`event-catalog.md`](event-catalog.md) отслеживает цепочку сообщений.

## Уровни в одной таблице

| Уровень | Что проверяет | Реальная БД | Реальный RabbitMQ | Другие сервисы | Живёт в |
| --- | --- | --- | --- | --- | --- |
| Unit | доменную сущность/VO | нет | нет | нет | `services/*/tests/Unit/` |
| Application | Handler, против fakes | нет | нет | нет | `services/*/tests/Integration/Application/` |
| Integration | один адаптер | да | иногда | нет | `services/*/tests/Integration/{Gateways,Messaging,Persistence,Transaction}/` |
| (Laravel Feature) | собственная HTTP/console-точка входа сервиса, in-process | да (sqlite) | нет (`Http::fake()`) | faked | `services/*/tests/Feature/` |
| Component | целый сервис, живой процесс | да | да | stub-сервер | не построено |
| Contract | схему одного события | нет | нет | нет | не построено |
| Service integration | 2-3 реальных сервиса + брокер | да | да | да (2-3) | `tests/integration/<slice>/` |
| E2E | полный бизнес-флоу | да | да | да (все) | `tests/e2e/<scenario>/` |
| Resilience | один failure-сценарий | да | да | да | `tests/resilience/<scenario>/` |
| Architecture | правила направления зависимостей | нет | нет | нет | `services/*/tests/Architecture/` |

## Статус

### Service integration (`tests/integration/`)

| Срез | Статус |
| --- | --- |
| `subscription-to-billing` | **Готово.** `POST /subscriptions` → реальный Outbox → реальный RabbitMQ → реальный `billing-events:consume` → Invoice Open. См. его собственный [README](../../tests/integration/subscription-to-billing/README.ru.md). |
| `billing-to-payment` | Не построено. `invoice.created.v1` → Payment автоматически создаёт и обрабатывает Payment. |
| `payment-to-billing` | Не построено. `payment.succeeded.v1`/`payment.failed.v1` → Billing транслирует в `invoice.paid.v1`/`invoice.payment_failed.v1`. |
| `billing-to-subscription` | Не построено. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription переходит в Active/PastDue. |
| `payment-to-notification` | Не построено. `payment.succeeded.v1` → Notification отправляет receipt. |

### Всё остальное в пирамиде

| Уровень | Статус |
| --- | --- |
| Component | Не построено. Будет жить по сервисам, например `services/subscription-service/tests/Component/`. |
| Contract | Не построено. Один producer-тест на событие из [каталога событий](event-catalog.ru.md), один consumer-тест на каждый сервис, который его читает. |
| E2E (`tests/e2e/`) | Не построено. Сначала нужны `billing-to-payment` и `payment-to-billing` — см. «Следующий срез» ниже. |
| Resilience (`tests/resilience/`) | Не построено. |
| `kind`-based platform smoke tests | Не построено. Отдельно от всего вышеперечисленного — см. ADR 0004, «Docker Compose для бизнес-тестов, Kubernetes — для платформенных». |

## Следующий срез

Не переходите сразу к полному E2E. Каждый service integration срез
строится, доказывается и мёрджится сам по себе — так же, как сама
цепочка событий RabbitMQ строилась по одному вертикальному срезу за
раз (см. историю [каталога событий](event-catalog.ru.md)). Конкретно,
для `billing-to-payment`:

1. Скопируйте `tests/integration/subscription-to-billing/` как
   отправную точку: та же форма `docker-compose.yaml` (Postgres,
   RabbitMQ, роли `-api` обоих сервисов + какие `-consumer`/`-outbox`
   нужны сценарию), тот же самостоятельный Pest-проект, тот же
   помощник `eventually()`.
2. Замените на `billing-api` + `billing-outbox` (billing уже
   публикует `invoice.created.v1` через
   `InvoiceCreatedIntegrationEvent`) и `payment-api` +
   `payment-consumer` (`invoice-created:consume`).
3. Тест: создайте Invoice напрямую через собственный HTTP API billing
   (не нужно идти через Subscription для этого среза — эта граница уже
   покрыта `subscription-to-billing`), затем через `eventually()`
   проверьте, что появился Payment через `GET /payments` на
   payment-service, `status: succeeded` (fake payment provider
   автоматически успешен).

Когда `billing-to-payment`, `payment-to-billing` и
`billing-to-subscription` будут построены, первый сценарий
`tests/e2e/` (`successful-subscription`) — это в основном сборка их
`docker-compose.yaml`-сервисов в один стек и написание одного теста,
который проходит всю цепочку через HTTP — не новая интеграционная
работа, а композиция уже существующей.

## Асинхронные проверки

См. ADR 0004, «Асинхронные проверки: polling, а не sleep». Помощник
`eventually()` сегодня живёт в
[`tests/integration/subscription-to-billing/tests/Support/eventually.php`](../../tests/integration/subscription-to-billing/tests/Support/eventually.php);
как только он понадобится второму срезу, он переезжает в общее место
(например, Composer-пакет `tests/support/`), а не копируется в третий
раз.

## Fake providers

Ещё не построено. Нужно до любого E2E- или resilience-теста, который
касается Payment или Notification: адаптер `PAYMENT_GATEWAY=fake` с
детерминированными исходами (известный token/card → success, decline
или timeout) и адаптер `NOTIFICATION_DRIVER=fake`, который
записывает, что он «отправил», вместо вызова настоящего провайдера.
У Payment уже есть `fake`-провайдер для автоматической обработки
первой попытки (см. поле `provider: "fake"` в записях платежа) — чего
не хватает для E2E, так это детерминированного *управления* исходом со
стороны теста, а не самого факта наличия fake-адаптера.
