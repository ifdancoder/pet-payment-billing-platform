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
| `billing-to-payment` | **Готово.** Billing консьюмит напрямую опубликованный `subscription.created.v1` → Invoice Open → реальный Outbox → реальный RabbitMQ → реальный `invoice-created:consume` → Payment succeeded. См. его собственный [README](../../tests/integration/billing-to-payment/README.ru.md). |
| `payment-to-billing` | Не построено. `payment.succeeded.v1`/`payment.failed.v1` → Billing транслирует в `invoice.paid.v1`/`invoice.payment_failed.v1`. |
| `billing-to-subscription` | Не построено. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription переходит в Active/PastDue. |
| `payment-to-notification` | Не построено. `payment.succeeded.v1` → Notification отправляет receipt. |

### Всё остальное в пирамиде

| Уровень | Статус |
| --- | --- |
| Component | Не построено. Будет жить по сервисам, например `services/subscription-service/tests/Component/`. |
| Contract | Не построено. Один producer-тест на событие из [каталога событий](event-catalog.ru.md), один consumer-тест на каждый сервис, который его читает. |
| E2E (`tests/e2e/`) | Не построено. Сначала нужны `payment-to-billing` и `billing-to-subscription` — см. «Следующий срез» ниже. |
| Resilience (`tests/resilience/`) | Не построено. |
| `kind`-based platform smoke tests | Не построено. Отдельно от всего вышеперечисленного — см. ADR 0004, «Docker Compose для бизнес-тестов, Kubernetes — для платформенных». |

## Следующий срез

Не переходите сразу к полному E2E. Каждый service integration срез
строится, доказывается и мёрджится сам по себе — так же, как сама
цепочка событий RabbitMQ строилась по одному вертикальному срезу за
раз (см. историю [каталога событий](event-catalog.ru.md)).

`billing-to-payment` выявил реальное ограничение, которое стоит нести
дальше: у Billing нет прямого HTTP-эндпоинта «создать Invoice» — он
создаёт его только консьюмя `subscription.created.v1`. Вместо того
чтобы тащить третий сервис в «2-сервисный» тест только ради
производства этого события, тест публикует его напрямую в exchange, в
том же самом wire-формате, что производит `RabbitMqEventPublisher`
(см.
[`tests/integration/billing-to-payment/tests/Support/EventPublisher.php`](../../tests/integration/billing-to-payment/tests/Support/EventPublisher.php)).
Этот же паттерн обобщается: для любого следующего service integration
теста сначала проверьте, действительно ли вышестоящему событию нужен
целый отдельный сервис, чтобы его произвести, или его можно
опубликовать напрямую (как это делала бы producer-сторона Contract
теста), сохраняя срез действительно 2-3-сервисным, а не расползающимся
к полной цепочке. Тест также сам объявляет и биндит целевую очередь
перед публикацией — публикация до того, как настоящий консьюмер в
своей первой итерации цикла успел забиндить очередь, молча теряет
сообщение на topic exchange; эту гонку стоит закрывать явно, а не
забивать тест задержкой на старте.

Конкретно, для `payment-to-billing` (следующий срез):

1. Скопируйте `tests/integration/billing-to-payment/` как отправную
   точку: та же форма `docker-compose.yaml`, тот же самостоятельный
   Pest-проект, тот же помощник `eventually()` — это будет уже второй
   раз, когда `eventually()` копируется, а не шарится; вынести его в
   общее место (например, Composer-пакет `tests/support/`) стоит на
   *третьем* срезе, а не раньше, чем он реально понадобится дважды.
2. Замените на `payment-api` + `payment-outbox` (payment уже публикует
   `payment.succeeded.v1`/`payment.failed.v1`) и `billing-api` +
   `billing-consumer` (`billing-events:consume` уже биндит оба routing
   key).
3. Тест: опубликуйте `payment.succeeded.v1` напрямую (у payment-service
   тоже нет прямого HTTP-эндпоинта «пометить платёж успешным» — та же
   логика, что выше), в том же wire-формате, что производит
   `PaymentSucceededIntegrationEvent`, затем через `eventually()`
   проверьте, что целевой Invoice перешёл в `status: paid` через
   `GET /invoices` на billing-service, и что `invoice.paid.v1`
   действительно был republish-нут (либо проверив это через
   `billing-to-subscription`, когда он появится, либо проверив, что
   сообщение попало в объявленную самим тестом очередь, забинженную на
   этот routing key).

Когда `payment-to-billing` и `billing-to-subscription` будут
построены, первый сценарий `tests/e2e/` (`successful-subscription`) —
это в основном сборка их `docker-compose.yaml`-сервисов в один стек и
написание одного теста, который проходит всю цепочку через HTTP — не
новая интеграционная работа, а композиция уже существующей.

## Асинхронные проверки

См. ADR 0004, «Асинхронные проверки: polling, а не sleep». Сегодня
помощник `eventually()` копируется в собственный `tests/Support/`
каждого среза — у `subscription-to-billing` и `billing-to-payment`
уже по идентичной собственной копии. Вынесите его в общее место
(например, Composer-пакет `tests/support/`), когда он понадобится
`payment-to-billing`, а не копируйте в третий раз.

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
