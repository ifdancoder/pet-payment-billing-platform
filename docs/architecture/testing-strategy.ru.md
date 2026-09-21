# Стратегия тестирования

*[English version](testing-strategy.md)*

Живая версия решения из [ADR 0004](../adr/0004-testing-strategy.ru.md)
— что реально построено сегодня, и что дальше. Собрана по факту кода и
дерева `tests/`, а не по целевому дизайну; обновляйте этот файл по мере
появления новых срезов, так же, как
[`event-catalog.ru.md`](event-catalog.ru.md) отслеживает цепочку сообщений.

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
| `payment-to-billing` | **Готово.** Напрямую опубликованный `subscription.created.v1` сеет Invoice, дальше *реальная* цепочка отрабатывает до конца (настоящий `billing-outbox`, настоящий `payment-consumer`, настоящий `payment-outbox`) до настоящего `payment.succeeded.v1` → Billing помечает Invoice как Paid и republish-ит `invoice.paid.v1` (проверено прямо на wire, не только через HTTP). См. его собственный [README](../../tests/integration/payment-to-billing/README.ru.md). |
| `billing-to-subscription` | Не построено. `invoice.paid.v1`/`invoice.payment_failed.v1` → Subscription переходит в Active/PastDue. |
| `payment-to-notification` | Не построено. `payment.succeeded.v1` → Notification отправляет receipt. |

### Всё остальное в пирамиде

| Уровень | Статус |
| --- | --- |
| Component | Не построено. Будет жить по сервисам, например `services/subscription-service/tests/Component/`. |
| Contract | Не построено. Один producer-тест на событие из [каталога событий](event-catalog.ru.md), один consumer-тест на каждый сервис, который его читает. |
| E2E (`tests/e2e/`) | Не построено. Сначала нужен `billing-to-subscription` — см. «Следующий срез» ниже. |
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

`payment-to-billing` уточнил этот план, столкнувшись со своим
собственным ограничением: чтобы пометить Invoice как Paid, он должен
уже существовать в базе billing (`MarkInvoicePaidHandler` ищет его по
ID и падает, если его нет), поэтому подделка `payment.succeeded.v1`
напрямую — изначальный план — означала бы выдумывание `payment_id` и
`invoice_id`, за которыми ничего реального не стоит, что доказывало бы
только, что консьюмер умеет распарсить корректно сформированное
сообщение, а не что граница работает с реальными, сгенерированными
системой, скоррелированными ID. Вместо этого: посеять один Invoice
напрямую опубликованным `subscription.created.v1` (тот же паттерн, что
в `billing-to-payment`), а дальше дать отработать *реальной* цепочке —
`invoice.created.v1`, Payment и `payment.succeeded.v1` производятся
настоящим кодом `billing-outbox`/`payment-consumer`/`payment-outbox`.
Прямая публикация — для того, чтобы засеять состояние, до которого тест
иначе не может дотянуться, а не короткий путь в обход настоящего
publish-пути сервиса, когда именно этот путь и является предметом
проверки.

Здесь же `eventually()` и механика publish/bind переехали в
[`tests/support/`](../../tests/support/), общий локальный
Composer-пакет — третий срез, ровно тогда, когда более ранняя версия
этого документа и предписывала extraction (см. «Асинхронные проверки»
ниже). Его `AmqpTestClient` обобщает и `EventPublisher` из
`billing-to-payment` (publish + declare/bind перед публикацией), и
добавляет `bindTestQueue()`: приватную одноразовую очередь, забинженную
на один routing key, для проверки, что сервис реально что-то
republish-нул на wire — а не просто что изменилось его собственное
HTTP-видимое состояние. `payment-to-billing` использует это, чтобы
подтвердить, что `invoice.paid.v1` реально несёт обратно
`subscription_id` — в этом и есть весь смысл этого hop-а трансляции.

Конкретно, для `billing-to-subscription` (следующий срез):

1. Скопируйте `tests/integration/payment-to-billing/` как отправную
   точку: та же форма `docker-compose.yaml`, та же зависимость от
   `tests/support/` через его `path`-репозиторий.
2. Замените на `billing-api` + `billing-outbox` и `subscription-api` +
   `subscription-consumer` (`subscription-events:consume` уже биндит и
   `invoice.paid.v1`, и `invoice.payment_failed.v1`).
3. Тест: посейте Invoice тем же способом (напрямую опубликованный
   `subscription.created.v1`) *и* Subscription — собственный консьюмер
   Subscription ищет Subscription по ID из payload события, так что он
   должен уже существовать, а прямого HTTP-эндпоинта подделать это тоже
   нет. Либо опубликуйте соответствующий `subscription.created.v1`
   через реальный create-флоу subscription-api (внеся сам
   subscription-service в этот стек, поскольку только он реально может
   создать строку Subscription), либо посейте его напрямую тоже —
   решите, когда реальное ограничение станет видно, так же, как дизайн
   `payment-to-billing` изменился, когда прояснилось его собственное
   ограничение. Затем через `eventually()` проверьте, что Subscription
   перешла в `status: active` через `GET /subscriptions` на
   subscription-service.

Когда `billing-to-subscription` будет построен, первый сценарий
`tests/e2e/` (`successful-subscription`) — это в основном сборка
`docker-compose.yaml`-сервисов всех срезов в один стек и написание
одного теста, который проходит всю цепочку через HTTP — не новая
интеграционная работа, а композиция уже существующей.

## Асинхронные проверки

См. ADR 0004, «Асинхронные проверки: polling, а не sleep». Помощник
`eventually()` живёт в [`tests/support/`](../../tests/support/) начиная
с `payment-to-billing`, третьего среза, которому он понадобился —
у `subscription-to-billing` и `billing-to-payment` всё ещё свои более
ранние, идентичные локальные копии в `tests/Support/`; их миграция на
общий пакет — последующая работа, пока не сделана.

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
