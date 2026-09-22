# Каталог событий

*[English version](event-catalog.md)*

Все события используют topic exchange `billing.events` и envelope из
[ADR 0002](../adr/0002-rabbitmq-messaging.ru.md).

## Потребляемые события

| Событие | Producer | Consumer и результат |
| --- | --- | --- |
| `subscription.created.v1` | subscription-service | billing-service создаёт инвойс |
| `subscription.renewal_due.v1` | subscription-service | billing-service создаёт инвойс следующего цикла |
| `invoice.created.v1` | billing-service | payment-service создаёт и обрабатывает платёж |
| `payment.succeeded.v1` | payment-service | billing-service переводит инвойс в Paid; notification-service создаёт уведомление |
| `payment.failed.v1` | payment-service | billing-service публикует `invoice.payment_failed.v1`; инвойс остаётся Open |
| `invoice.paid.v1` | billing-service | subscription-service активирует подписку |
| `invoice.payment_failed.v1` | billing-service | subscription-service переводит Active-подписку в PastDue; Pending-подписка остаётся Pending |

События Payment содержат `invoice_id`, но не `subscription_id`. Billing владеет
связью инвойса с подпиской и преобразует результаты платежа в события инвойса
для Subscription.

## События без consumers

| Событие | Producer |
| --- | --- |
| `subscription.activated.v1` | subscription-service |
| `subscription.canceled.v1` | subscription-service |
| `subscription.past_due.v1` | subscription-service |
| `invoice.voided.v1` | billing-service |
| `customer.created.v1` | customer-service |
| `product.created.v1` | catalog-service |
| `product.archived.v1` | catalog-service |
| `price.created.v1` | catalog-service |
| `price.activated.v1` | catalog-service |
| `price.deactivated.v1` | catalog-service |

При изменении integration event или queue binding обновите соответствующую
строку.
