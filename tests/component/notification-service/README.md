# Component: notification-service

*[Русская версия](README.ru.md)*

The platform's second Component test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)),
same shape as
[`tests/component/subscription-service/`](../subscription-service/):
exactly one real service, its own real Postgres and real RabbitMQ, and
a WireMock stub standing in for its one synchronous HTTP dependency
(customer-service) — never the real service.

## Why this one has no publish side to test

Unlike `subscription-service`, `notification-service` publishes
nothing onto `billing.events` (see the
[event catalog](../../../docs/architecture/event-catalog.md)) — there's
no Outbox, no downstream event to assert on the wire. Its only job here
is reacting correctly to a directly-published `payment.succeeded.v1`,
standing in for `payment-service`, which isn't part of this stack at
all. `payment-service` actually publishing that event is already
covered by
[`tests/integration/billing-to-payment/`](../../integration/billing-to-payment/).

## Why this is a different question from `payment-to-notification`

[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/)
already proves `PaymentSucceededConsumer`'s "no contact, no
notification" guard against a *real* customer-service, by creating a
merchant/customer relationship that then goes missing. Here, the same
guard costs nothing to arrange: one fixed sentinel customer id that the
stub always answers 404 for
(`wiremock/mappings/customer-not-found.json`), rather than provisioning
real broken state. The delivery worker
(`notifications:deliver`) needs no stub of its own — `FakeEmailSender`
is an in-process fake, not an HTTP call — so this test also exercises
that worker for real, something neither
`tests/integration/payment-to-notification/` nor a unit test of the
consumer alone would.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one database (`notification`) |
| `rabbitmq` | the real broker — AMQP port published, since the test publishes directly |
| `customer-stub` | WireMock, standing in for customer-service |
| `notification-api` | the real HTTP API — exposes `GET /notifications` for the test's assertions |
| `notification-ingest-consumer` | the real `payment-succeeded:consume` loop — the thing under test |
| `notification-delivery-worker` | the real `notifications:deliver` loop, using `FakeEmailSender` |

## Running it

```bash
cd tests/component/notification-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live

`notification-ingest-consumer`'s own logs showed exactly 2 `Consumed
1` lines — one per test — and `notification-delivery-worker` showed
exactly one `Delivered 1 notification(s).`, matching only the
happy-path test's notification (the unknown-customer test never
creates one to deliver). `GET /__admin/requests` on the WireMock
container showed both real lookups — the sentinel 404 id and the
happy-path customer id — confirming both tests actually round-tripped
over the network rather than hitting an in-process fake.
