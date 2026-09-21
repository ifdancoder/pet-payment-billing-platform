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
| `billing-to-subscription` | **Готово.** Два теста: напрямую опубликованный `invoice.paid.v1` активирует реальную, созданную через HTTP Pending-подписку; напрямую опубликованный `invoice.payment_failed.v1` помечает её PastDue, но только когда она реально уже Active (проверяет собственное guard-условие `HandleInvoicePaymentFailedHandler`, а не только сам переход). См. его собственный [README](../../tests/integration/billing-to-subscription/README.ru.md). |
| `payment-to-notification` | **Готово.** Два теста: happy path (напрямую опубликованный `payment.succeeded.v1` в итоге доставляет email-receipt со статусом Sent), и негативный случай, доказывающий собственный guard `PaymentSucceededConsumer` — неизвестный `customer_id` не записывает ни запись в Inbox, ни Notification, так что повторная доставка может просто повторить поиск позже. См. его собственный [README](../../tests/integration/payment-to-notification/README.ru.md). |

### Всё остальное в пирамиде

| Уровень | Статус |
| --- | --- |
| Component | Не построено. Будет жить по сервисам, например `services/subscription-service/tests/Component/`. |
| Contract | Не построено. Один producer-тест на событие из [каталога событий](event-catalog.ru.md), один consumer-тест на каждый сервис, который его читает. |
| E2E (`tests/e2e/`) | **Первый сценарий готов: `successful-subscription`.** Все семь сервисов, реальный Postgres, реальный RabbitMQ, нигде никаких трюков с прямой публикацией — Merchant → Customer → Product/Price → Subscription → Invoice → Payment → Subscription Active → Notification, полностью пройдено через реальный HTTP. См. его собственный [README](../../tests/e2e/successful-subscription/README.ru.md). |
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

`billing-to-subscription` разрешил свою версию того же вопроса чисто,
потому что у Subscription (в отличие от Invoice или Payment) есть
настоящий эндпоинт `POST /subscriptions`: не понадобился трюк с прямой
публикацией, чтобы его посеять — просто настоящий запрос через
настоящий create-флоу, поэтому `customer-service` и `catalog-service`
в этом стеке, хотя ни один из них не является границей под тестом (этот
флоу вызывает их синхронно). Самого billing-service в этом стеке
*нет* — публикация им обоих событий уже покрыта `payment-to-billing`,
поэтому тест публикует `invoice.paid.v1`/`invoice.payment_failed.v1`
напрямую, та же логика, что и во всех предыдущих срезах. Это также
первый срез, проверяющий *guard-условие*, а не просто переход:
`HandleInvoicePaymentFailedHandler` срабатывает только на `Active` →
`PastDue`, поэтому его второй тест сначала реально доводит подписку до
Active (публикуя `invoice.paid.v1` и дожидаясь) прежде чем опубликовать
`invoice.payment_failed.v1` — самый первый неудачный платёж Pending-
подписки должен остаться Pending, и отличить эти два случая может
только тест, который реально сначала дошёл до Active.

Когда `subscription-to-billing`, `billing-to-payment`,
`payment-to-billing` и `billing-to-subscription` были готовы,
`successful-subscription` оказался в основном композицией, ровно как и
предполагалось: те же самые сервисы `docker-compose.yaml`, что уже
использовал каждый срез, собранные в один стек из 16 контейнеров (все
семь сервисов, не «2-3» — см. таблицу пирамиды), и один тест, который
проходит всю цепочку через настоящий HTTP без единого трюка с прямой
публикацией — реально создать Subscription, через `eventually()`
проверить Invoice → Paid, Payment → succeeded, Subscription → `active`,
и Notification-receipt, в этом порядке. Проверено вживую: ровно одно
сообщение на каждом шаге по всей цепочке (проверено в логах каждого
воркера, а не просто выведено из прохождения HTTP-проверок), без
дублей, без потерь.

Построение этого выявило одну вещь, стоящую того, чтобы её унести
дальше — то, чего не показал бы срез поменьше: когда контейнеры всех
семи сервисов стартуют одновременно вместо двух-трёх, стартовая гонка,
которая на самом деле есть в любом `docker-compose.yaml` этого
репозитория — worker `-consumer`/`-outbox` зависит только от здоровья
`postgres`, а не от завершения миграции собственного `-api`-контейнера
— впервые проявилась как реально наблюдаемая, залогированная ошибка
(`billing-outbox` опросил `outbox_messages` до того, как `migrate
--force` в `billing-api` успел её создать). Она самовосстановилась в
собственном цикле повтора и не завалила тест, поэтому она
задокументирована, а не «исправлена» — полное обоснование, почему это
правильное решение для одноразового, single-replica-на-сервис
compose-стека (в отличие от Kubernetes, где отдельный migrate Job
существует именно для того, чтобы это исключить) — в собственном
README `successful-subscription`.

`payment-to-notification` закрыл последний срез границы событий, и
добавил то, что не понадобилось ни одному из первых четырёх:
негативный тест. Собственный guard `PaymentSucceededConsumer` —
неизвестный customer означает, что ни Inbox, ни Notification не
записываются, так что повторная доставка может просто повторить поиск
позже — это утверждение о том, что что-то *не* происходит, а
`eventually()` рассчитан на ожидание, пока условие не станет истинным,
а не на доказательство, что оно остаётся ложным. Его второй тест вместо
этого даёт неправильному поведению реальное окно (фиксированный
`sleep(3)`, несколько настоящих итераций цикла worker-а), прежде чем
проверить, что список notifications всё ещё пуст. Это не тот
анти-паттерн «`sleep()` вместо `eventually()`», от которого предостерегает
ADR 0004 — это единственный честный способ проверить отсутствие; polling
означал бы только «ещё не произошло», а не «не происходит».

Когда каждая граница событий в основной цепочке платформы теперь
покрыта своим собственным service integration срезом, что дальше, и
ничто из этого не блокирует то, что уже есть:

- `tests/e2e/failed-payment/` и `tests/e2e/overdue-subscription/` —
  обоим сначала нужно детерминированное *управление* исходом
  fake-провайдера платежей со стороны теста (см. «Fake providers»
  ниже); пока не строятся не потому, что fake-провайдера нет, а
  потому, что его пока нельзя попросить провалиться намеренно.
- `tests/resilience/` — повторная доставка, падение консьюмера,
  недоступность RabbitMQ, восстановление Outbox. Каждый из них может
  переиспользовать `docker-compose.yaml` какого-нибудь service
  integration среза как стартовый стек — так же, как
  `successful-subscription` переиспользовал стеки всех четырёх service
  integration срезов, существовавших на тот момент.
- Уровни Component и Contract — всё ещё вообще не начаты; см. их
  собственные строки в таблице статуса выше.

## Асинхронные проверки

См. ADR 0004, «Асинхронные проверки: polling, а не sleep». Помощник
`eventually()` живёт в [`tests/support/`](../../tests/support/) начиная
с `payment-to-billing`, третьего среза, которому он понадобился —
у `subscription-to-billing` и `billing-to-payment` всё ещё свои более
ранние, идентичные локальные копии в `tests/Support/`; их миграция на
общий пакет — последующая работа, пока не сделана.

## Fake providers

Уже безопасно для happy path, менее полно для failure-путей.
`IPaymentGatewayPort` и `IEmailSenderPort` оба безусловно забиндены на
свои реализации `Fake*` в собственном `ServiceProvider` каждого
сервиса — не зависят от окружения, нигде ещё не заменены на настоящего
провайдера — так что ни один E2E-тест сегодня не может случайно
попасть на живой платёжный процессинг или отправить настоящий email:
для этого просто нет пути в коде. При этом `FakePaymentGateway::charge()`
и `FakeEmailSender::send()` оба всегда безусловно возвращают успех —
ровно то, что нужно `successful-subscription`, и ничего больше.

Чего реально не хватает — это *детерминированного управления* исходом
со стороны теста, нужного для `tests/e2e/failed-payment/` и любого
resilience-сценария, где платёж должен провалиться намеренно
(известный token/card → decline или timeout, выбираемый для конкретного
запроса), а не всегда успешно проходить. Пока этого нет, строить можно
только happy-path E2E-сценарий; failure-сценариям сначала нужен этот
кусок.
