# Event catalog

*[Русская версия](event-catalog.ru.md)*

All events use the `billing.events` topic exchange and the envelope defined in
[ADR 0002](../adr/0002-rabbitmq-messaging.md).

## Consumed events

| Event | Producer | Consumer and effect |
| --- | --- | --- |
| `subscription.created.v1` | subscription-service | billing-service creates an invoice |
| `subscription.renewal_due.v1` | subscription-service | billing-service creates the next cycle's invoice |
| `invoice.created.v1` | billing-service | payment-service creates and processes a payment |
| `payment.succeeded.v1` | payment-service | billing-service marks the invoice Paid; notification-service creates a receipt notification |
| `payment.failed.v1` | payment-service | billing-service publishes `invoice.payment_failed.v1`; the invoice remains Open |
| `invoice.paid.v1` | billing-service | subscription-service activates the subscription |
| `invoice.payment_failed.v1` | billing-service | subscription-service marks an Active subscription PastDue; a Pending subscription remains Pending |

Payment events contain `invoice_id`, not `subscription_id`. Billing owns the
invoice-to-subscription relationship and translates payment results into invoice
events for Subscription.

## Events without consumers

| Event | Producer |
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

Add or update the corresponding row when an integration event or queue binding
changes.
