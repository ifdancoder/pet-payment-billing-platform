# 2. RabbitMQ messaging contract

*[Русская версия](0002-rabbitmq-messaging.ru.md)*

## Status

Accepted

## Context

Subscription, Billing, Payment, Customer, and Catalog publish integration events.
Billing, Payment, Subscription, and Notification consume them. A common contract
is required for delivery, deduplication, envelopes, and queue ownership.

## Decision

### Reliability

- Delivery is at least once. Consumers must tolerate redelivery.
- Publishers use the durable `billing.events` topic exchange.
- A local write and its outbox row commit in one database transaction.
- An inbox row, consumer effects, and any outgoing outbox rows commit in one
  transaction.
- `event_id` deduplicates transport redelivery. Unique business keys handle
  separate events that represent the same business fact.
- Consumers acknowledge only after the database transaction commits.
- Failed deliveries are republished with an incremented `delivery_attempt`.
  After three attempts they are rejected to the consumer queue's durable DLQ.
- Retry publication is publisher-confirmed before the original message is
  acknowledged.
- Event order is not guaranteed. Domain state transitions must reject or ignore
  invalid late events.
- PostgreSQL is the system of record. RabbitMQ is not used for event sourcing.

### Naming and envelope

Event types use `<entity>.<event>.v<version>`. A breaking payload change gets a
new version.

The JSON body contains only the business payload. AMQP application headers
contain `event_id`, `aggregate_type`, `aggregate_id`, `occurred_at`,
`correlation_id`, and `causation_id`. Message properties contain
`content_type=application/json`, persistent delivery mode, `message_id`,
`correlation_id`, and the event timestamp. The routing key is the event type.

### Queue topology

A queue belongs to a consuming capability, not to an event type. One queue may
bind several routing keys. Current application queues are `billing.events.v1`,
`subscription.events.v1`, `payment.invoice-created`, and
`notification.payment-succeeded`. Each queue has a durable `.dlq`.

## Consequences

Publishing and local persistence do not require a distributed transaction.
Duplicate delivery remains possible and is part of the contract. Immediate
retries are bounded but have no delayed backoff. Adding an event requires a
producer contract test and an update to
[the event catalog](../architecture/event-catalog.md).
