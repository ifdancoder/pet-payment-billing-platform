# Общая тестовая инфраструктура

*[English version](README.md)*

Небольшая Composer-библиотека (`billing-platform/test-support`),
подключаемая в Pest-проекты `tests/integration/*/`, `tests/e2e/*/` и
`tests/resilience/*/` через `path`-репозиторий — не сервис, не
шарится ни с чьим `vendor/`. См.
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).

## Что внутри

- **`eventually()`** — опрашивает проверку, пока она не пройдёт или не
  истечёт таймаут, для проверок на асинхронной цепочке (HTTP-вызов →
  Outbox → RabbitMQ → отдельный процесс-консьюмер) без фиксированного
  `sleep()`.
- **`AmqpTestClient`** — обобщённый тестовый клиент для exchange
  `billing.events`: опубликовать событие напрямую (заменяя собой Outbox
  вышестоящего сервиса, когда тащить весь этот сервис в стек ради
  одного события бессмысленно), объявить/забиндить очередь настоящего
  консьюмера перед публикацией в неё (закрывает реальную гонку — topic
  exchange теряет сообщение, опубликованное до того, как хоть одна
  очередь забиндена на его routing key), либо забиндить приватную
  одноразовую очередь, чтобы проверить, что другой сервис реально
  что-то republish-нул. `publish()` принимает опциональный явный
  `eventId` — добавлен ради
  [`tests/resilience/duplicate-delivery/`](../resilience/duplicate-delivery/),
  которому нужно опубликовать *один и тот же* `event_id` дважды, чтобы
  симулировать настоящую at-least-once передоставку, а не два
  независимых события, которые просто похожи друг на друга.

## Использование в Pest-проекте

```json
{
    "repositories": [{"type": "path", "url": "../../support"}],
    "require": {
        "billing-platform/test-support": "@dev"
    }
}
```

Именно `@dev`, не `*` — пакет из `path`-репозитория без тега
резолвится как `dev-main`, что `composer install` иначе отвергает
против стандартного `minimum-stability: stable`.

## История извлечения

Построено не заранее «на всякий случай» — `eventually()` сначала был
скопирован в собственный `tests/Support/` первых двух срезов
([`subscription-to-billing/`](../integration/subscription-to-billing/),
[`billing-to-payment/`](../integration/billing-to-payment/)), и
переехал сюда только когда он понадобился третьему срезу
([`payment-to-billing/`](../integration/payment-to-billing/)) — см.
правило extraction в
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).
У первых двух срезов всё ещё свои локальные копии; их миграция на этот
пакет — последующая работа, пока не сделана.
