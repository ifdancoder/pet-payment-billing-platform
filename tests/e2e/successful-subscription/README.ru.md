# E2E: successful subscription

*[English version](README.md)*

Первый end-to-end сценарий платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Все семь сервисов, реальный Postgres, реальный RabbitMQ, нигде никаких
трюков с прямой публикацией: Merchant → Customer → Product/Price →
Subscription → Invoice → Payment → Subscription Active → Notification,
полностью пройдено через реальный HTTP, каждое событие на wire
производится тем сервисом, чья это реально работа.

## Не новая интеграционная работа — композиция

Каждый шаг здесь уже был доказан в изоляции срезом
[service integration](../../integration/), каждый из которых намеренно
пропускает 4-5 сервисов и подделывает одно вышестоящее событие, чтобы
остаться сфокусированным 2-3-сервисным тестом:

- [`subscription-to-billing/`](../../integration/subscription-to-billing/) — Subscription → Billing
- [`billing-to-payment/`](../../integration/billing-to-payment/) — Billing → Payment
- [`payment-to-billing/`](../../integration/payment-to-billing/) — Payment → Billing (трансляция `invoice.paid.v1`)
- [`billing-to-subscription/`](../../integration/billing-to-subscription/) — Billing → Subscription (активация)

Чего ни один из них не мог доказать сам по себе: что вся цепочка
держится end-to-end, когда система сама генерирует и прокидывает
каждый ID, и что Notification — консьюмя `payment.succeeded.v1`
независимо от собственного консьюминга этого же события Billing —
реально срабатывает параллельно с остальной цепочкой, а не после неё.
Именно для этого и существует этот тест.

## Что поднимается

Все семь сервисов, каждый со всеми своими workload-ролями (`api` плюс
какие есть роли `consumer`/`outbox` — см.
[`docker-compose.yaml`](docker-compose.yaml)), плюс `postgres` и
`rabbitmq`. Identity включён, хотя пока ничто дальше по цепочке не
проверяет созданного им мерчанта — первый шаг сценария всё равно
«Merchant» по собственному целевому флоу платформы (см. корневой
README).

## Запуск

```bash
cd tests/e2e/successful-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Реальная, самовосстанавливающаяся гонка — стоит знать

`billing-outbox` и `billing-consumer` не зависят от завершения миграции
`billing-api` — только от здоровья `postgres`, как и в любом
`docker-compose.yaml` в этом репозитории (см.
[`tests/integration/subscription-to-billing/docker-compose.yaml`](../../integration/subscription-to-billing/docker-compose.yaml)
про то, почему миграция inline здесь нормальна). При семи одновременно
стартующих сервисах вместо двух это реально один раз проявилось:
`billing-outbox` начал опрашивать `outbox_messages` до того, как
`migrate --force` в `billing-api` успел её создать, залогировал одну
ошибку `SQLSTATE[42P01]: Undefined table`, и его цикл повтора `while
true` догнался парой секунд позже — тест всё равно прошёл чисто,
поскольку `eventually()` уже терпит именно такой стартовый джиттер.
Оставлено как есть, а не «исправлено»: это разовая стоимость холодного
старта без наблюдаемого эффекта на тест, а нормальный fix (каждый
worker либо идемпотентно мигрирует сам, либо явный `depends_on` на
готовность api-контейнера) — это Kubernetes-style забота, которая этому
одноразовому, single-replica-на-сервис compose-стеку реально не нужна
— см.
[`docs/adr/0003-kubernetes-foundation.md`](../../../docs/adr/0003-kubernetes-foundation.md)
про то, почему Kubernetes решает это иначе (отдельный migrate Job,
запускаемый один раз, до того как Deployment вообще начнёт rollout).
