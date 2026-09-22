# Service integration: Subscription → Billing

*[English version](README.md)*

Первый service integration test в тестовой пирамиде платформы (см.
[`docs/architecture/testing-strategy.ru.md`](../../../docs/architecture/testing-strategy.ru.md)).
Проверяет именно то, что относится к этой границе — Subscription
публикует `subscription.created.v1` через реальный Outbox в реальный
RabbitMQ, Billing консьюмит событие и открывает Invoice — и ничего
сверх этого. Это не E2E: здесь нет Identity, Payment, Notification.

## Что поднимается

| Сервис | Роль |
| --- | --- |
| `postgres` | один инстанс, отдельная логическая БД под каждый сервис |
| `rabbitmq` | настоящий брокер, без моков |
| `customer-service` | синхронная зависимость subscription-api (должен найти customer) |
| `catalog-service` | синхронная зависимость subscription-api (должен найти price) |
| `subscription-api` | создаёт Subscription + строку в Outbox |
| `subscription-outbox` | настоящий Outbox relay, та же команда, что и в Kubernetes |
| `billing-api` | отдаёт `GET /invoices` для проверки в тесте |
| `billing-consumer` | настоящий цикл `billing-events:consume`, который превращает событие в Invoice |

Каждый контейнер сервиса перед стартом выполняет `php artisan migrate
--force` — нормально для одного одноразового инстанса; паттерн
«миграция отдельным Job» в Kubernetes существует именно чтобы избежать
гонки N реплик, а здесь этой гонки нет.

## Запуск

```bash
cd tests/integration/subscription-to-billing
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Почему `eventually()`, а не `sleep()`

Ответ 201 от `POST /subscriptions` доказывает только то, что Subscription
и строка Outbox записаны — но не то, что Billing уже что-то обработал.
Это происходит в собственных циклах опроса `subscription-outbox` и
`billing-consumer`, полностью асинхронно. Проверять сразу — это гонка;
фиксированный `sleep(N)` одновременно медленный (всегда платит худший
случай) и всё равно нестабильный (нагруженный CI может гонку проиграть).
[`eventually()`](../../support/src/eventually.php) из общего пакета
[`tests/support/`](../../support/) опрашивает саму проверку, пока она
не перестанет падать, либо не истечёт таймаут.

## Развитие сценария

По документу тестовой стратегии, следующие вертикальные срезы (каждый —
свой отдельный service integration test, не добавляется в этот) — это
Billing → Payment и Payment → Billing → Subscription, прежде чем они
склеятся в полноценный E2E-сценарий.
