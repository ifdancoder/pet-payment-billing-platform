# Service integration tests

*[Русская версия](README.ru.md)*

Each suite exercises one asynchronous boundary between running services with real PostgreSQL and RabbitMQ.

- `subscription-to-billing/`: subscription creation opens an invoice.
- `billing-to-payment/`: a new invoice creates and processes a payment.
- `payment-to-billing/`: payment success marks an invoice Paid and emits `invoice.paid.v1`.
- `billing-to-subscription/`: invoice results update subscription state.
- `payment-to-notification/`: payment success creates and delivers a receipt.

Upstream events may be published directly when their producer is outside the tested boundary.
