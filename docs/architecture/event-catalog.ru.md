# Каталог событий

*[English version](event-catalog.md)*

Каждое интеграционное событие, публикуемое сегодня в `billing.events`,
его producer и кто его реально консьюмит — не кто *должен* когда-нибудь,
если только это не отмечено как известный пробел. Собрано напрямую по
классам `IntegrationEvent`, классам `Consume*Command` и биндингам
очередей в коде, а не по целевому дизайну. Контракт, которому подчиняется
этот каталог — [ADR 0002](../adr/0002-rabbitmq-messaging.md).

## Связано end-to-end

Опубликовано, потреблено и покрыто Inbox на стороне консьюмера —
полные асинхронные вертикальные срезы платформы на сегодня.

| Событие | Producer | Consumer(ы) |
| --- | --- | --- |
| `subscription.created.v1` | subscription-service | billing-service (`SubscriptionCreatedConsumer` → создаёт `Invoice`) |
| `subscription.renewal_due.v1` | subscription-service | billing-service (`SubscriptionRenewalDueConsumer` → создаёт `Invoice` следующего billing-цикла) |
| `invoice.created.v1` | billing-service | payment-service (`InvoiceCreatedConsumer` → создаёт и обрабатывает `Payment`; `billing_reason` отличает `subscription_create` от `subscription_cycle`) |
| `payment.succeeded.v1` | payment-service | notification-service (`PaymentSucceededConsumer` → отправляет email-receipt); billing-service (`PaymentSucceededConsumer` → помечает `Invoice` как Paid, публикует `invoice.paid.v1`) |
| `payment.failed.v1` | payment-service | billing-service (`PaymentFailedConsumer` → сам `Invoice` не меняется, остаётся Open в ожидании следующей попытки, но это republish-ит `invoice.payment_failed.v1`) |
| `invoice.paid.v1` | billing-service | subscription-service (`InvoicePaidConsumer` → активирует `Subscription`, идемпотентно при каждом продлении, поскольку обычно уже Active ко второму платежу) |
| `invoice.payment_failed.v1` | billing-service | subscription-service (`InvoicePaymentFailedConsumer` → помечает `Subscription` как PastDue, но только если была Active — самый первый неудачный платёж Pending-подписки остаётся Pending, не PastDue) |

Это замыкает и начальную, и recurring цепочки событий платформы
end-to-end: Subscription → Billing → Payment → (Billing, Subscription,
Notification).

`payment.succeeded.v1` / `payment.failed.v1` никогда не несут
`subscription_id` — Payment вообще не моделирует подписки, только
`invoice_id`. Billing — естественная точка трансляции (его собственный
`Invoice` уже связывает `invoice_id` ↔ `subscription_id`), поэтому
именно он republish-ит, а не Subscription консьюмит эти два события
напрямую. Полное обоснование — в
[ADR 0002](../adr/0002-rabbitmq-messaging.md).

## Опубликовано, но пока без консьюмера

Реальные пробелы относительно собственной целевой цепочки событий
платформы (см. диаграмму системы в корневом README) — не гипотетические
события, а те, что уже летят на exchange, но их никто не слушает.

| Событие | Producer | Отсутствующий consumer(ы) | Почему это важно |
| --- | --- | --- | --- |
| `subscription.activated.v1` | subscription-service | нет | Известной потребности пока нет. |
| `subscription.canceled.v1` | subscription-service | нет | Уведомление-подтверждение отмены консьюмило бы это; не построено. |
| `subscription.past_due.v1` | subscription-service | нет | Известной потребности пока нет. |
| `invoice.voided.v1` | billing-service | нет | Известной потребности пока нет. |
| `customer.created.v1` | customer-service | нет | Известной потребности пока нет. |
| `product.created.v1` | catalog-service | нет | Известной потребности пока нет. |
| `product.archived.v1` | catalog-service | нет | Известной потребности пока нет. |
| `price.created.v1` | catalog-service | нет | Известной потребности пока нет. |
| `price.activated.v1` | catalog-service | нет | Известной потребности пока нет. |
| `price.deactivated.v1` | catalog-service | нет | Известной потребности пока нет. |

По эмпирическому правилу из раздела про именование в ADR 0002 — событие
без консьюмера, вероятно, пока не стоит публиковать — все они безобидны
(дёшево продолжать публиковать, готовы к тому дню, когда появится
консьюмер).

Пять событий catalog-service раньше были совсем другой категорией: они
были не «опубликованы, но без консьюмера», а **вообще не публиковались**
— `IEventPublisherPort` был безусловно забинден на `LogEventPublisher`,
так что строки Outbox помечались опубликованными, хотя только
логировались. Исправлено: у catalog-service теперь тот же биндинг
`RabbitMqEventPublisher` + `AMQPChannel`, что и у любого другого
публикующего сервиса.

## Envelope

Точное разделение header/body — в
[ADR 0002](../adr/0002-rabbitmq-messaging.md#envelope). Коротко: JSON
body — это только собственный бизнес-payload события; `event_id`,
`aggregate_type`, `aggregate_id` и `occurred_at` живут в AMQP-заголовках,
а routing key — это сама строка типа события.

## Добавление события в каталог

Когда выходит новый класс `IntegrationEvent`, добавьте строку сюда тем
же коммитом. Событие, которое существует в коде, но не в этом каталоге
— это ровно тот вид скрытой связанности, для предотвращения которого
существует этот документ.
