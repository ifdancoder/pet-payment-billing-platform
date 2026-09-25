# billing-service

*[Русская версия](README.ru.md)*

Owns invoices and billing cycles. It consumes subscription and payment events, then publishes invoice lifecycle events.

## Interfaces

- HTTP: list and fetch invoices; void an open invoice.
- Console workers: `billing-events:consume`, `outbox:publish`.
- Messaging: Consumes `subscription.created.v1`, `subscription.renewal_due.v1`, `payment.succeeded.v1`, and `payment.failed.v1`. Publishes `invoice.created.v1`, `invoice.paid.v1`, `invoice.payment_failed.v1`, and `invoice.voided.v1`.

The public routes are documented in [the OpenAPI contract](../../docs/openapi/openapi.yaml).
The shared event envelope and delivery rules are defined in
[ADR 0002](../../docs/adr/0002-rabbitmq-messaging.md).

## Development

```bash
composer install
composer test -- --compact
```

Use the root `docker-compose.yaml` or Kubernetes manifests for cross-service
execution. This service expects its own logical PostgreSQL database.
