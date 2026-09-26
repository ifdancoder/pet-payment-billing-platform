# 2. Контракт обмена сообщениями RabbitMQ

*[English version](0002-rabbitmq-messaging.md)*

## Статус

Accepted

## Контекст

Subscription, Billing, Payment, Customer и Catalog публикуют integration events.
Billing, Payment, Subscription и Notification их потребляют. Для delivery,
deduplication, envelope и владения очередями нужен единый контракт.

## Решение

### Надёжность

- Delivery использует at least once. Consumers должны допускать redelivery.
- Publishers используют durable topic exchange `billing.events`.
- Локальное изменение и outbox row фиксируются одной транзакцией базы.
- Inbox row, эффекты consumer и исходящие outbox rows фиксируются одной
  транзакцией.
- `event_id` устраняет transport redelivery. Отдельные события об одном
  бизнес-факте ограничиваются уникальным business key.
- Consumer подтверждает сообщение только после commit транзакции базы.
- Неудачная доставка публикуется повторно с увеличенным `delivery_attempt`.
  После трёх попыток сообщение отклоняется в durable DLQ очереди consumer.
- Retry подтверждается publisher confirm до acknowledgement исходного сообщения.
- Порядок событий не гарантируется. Domain state transitions должны отклонять
  или игнорировать некорректные поздние события.
- PostgreSQL является system of record. RabbitMQ не используется для event
  sourcing.

### Naming и envelope

Тип события имеет форму `<entity>.<event>.v<version>`. Несовместимое изменение
payload получает новую версию.

JSON body содержит только business payload. AMQP application headers содержат
`event_id`, `aggregate_type`, `aggregate_id`, `occurred_at`, `correlation_id` и
`causation_id`. Message properties содержат `content_type=application/json`,
persistent delivery mode, `message_id`, `correlation_id` и timestamp события.
Routing key равен типу события.

### Топология очередей

Очередь принадлежит consuming capability, а не типу события. Одна очередь может
иметь несколько routing-key bindings. Текущие application queues:
`billing.events.v1`, `subscription.events.v1`, `payment.invoice-created` и
`notification.payment-succeeded`. У каждой очереди есть durable `.dlq`.

## Последствия

Для публикации и локального хранения не нужна распределённая транзакция.
Повторная доставка возможна и является частью контракта. Немедленные retries
ограничены, но не имеют delayed backoff. Новое событие требует producer contract
test и обновления [каталога событий](../architecture/event-catalog.ru.md).
