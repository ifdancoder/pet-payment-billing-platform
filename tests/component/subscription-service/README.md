# Component: subscription-service

*[Русская версия](README.ru.md)*

The platform's first Component test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Exactly one real service, its own real Postgres and real RabbitMQ, and
a stub server standing in for its two synchronous HTTP dependencies
(customer-service, catalog-service) — never the real services, unlike
every [`tests/integration/`](../../integration/) stack.

## Why this is a different question from `subscription-to-billing`

[`tests/integration/subscription-to-billing/`](../../integration/subscription-to-billing/)
already proves this exact create flow works with the *real*
customer-service and catalog-service in the loop, and that a real
billing-service actually consumes `subscription.created.v1`. This test
asks something narrower and cheaper: is `subscription-service`'s own
container correct in isolation — its HTTP API, its Outbox/RabbitMQ
publish path, and its own RabbitMQ consume path — without paying to
boot two or more extra services just to answer that. It's also where a
guard condition that would be slow or awkward to arrange against a
real customer-service (an unknown customer, specifically) becomes
trivial: one fixed stub mapping instead of provisioning broken state in
a real service.

`billing-service` publishing `invoice.paid.v1`/`invoice.payment_failed.v1`
correctly is already covered by
[`tests/integration/payment-to-billing/`](../../integration/payment-to-billing/),
so the consume-side test here publishes directly — the same reasoning
every `tests/integration/` slice uses for an upstream event it isn't
the one under test.

## Why WireMock, not a hand-rolled stub

`customer-service`'s and `catalog-service`'s responses subscription-service
actually reads are pure lookups — no business logic, just JSON keyed by
whatever id was requested (see
[`HttpCustomerGateway`](../../../services/subscription-service/app/Infrastructure/Subscription/Adapters/Gateways/HttpCustomerGateway.php)
and
[`HttpCatalogGateway`](../../../services/subscription-service/app/Infrastructure/Subscription/Adapters/Gateways/HttpCatalogGateway.php)).
[WireMock](https://wiremock.org/)'s declarative JSON mappings
(`wiremock/mappings/`) express that directly, with no code to write or
maintain: `--global-response-templating` lets a mapping's response
echo back a path segment from the actual request (`{{request.path.[3]}}`)
so the stub answers *any* customer/price id with a fixed
customer/price, rather than requiring the test to know every id up
front. One instance serves both APIs — they're both just "look up a
resource by id over HTTP" on different paths, so routing both through
one WireMock container is simpler than running two.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance, one database (`subscription`) — this stack has only one service |
| `rabbitmq` | the real broker — AMQP port published, since the test publishes/reads directly |
| `customer-catalog-stub` | WireMock, standing in for both customer-service and catalog-service |
| `subscription-api` | the real HTTP API — the thing under test |
| `subscription-outbox` | the real Outbox relay |
| `subscription-consumer` | the real `subscription-events:consume` loop, reacting to `invoice.paid.v1`/`invoice.payment_failed.v1` |

## Running it

```bash
cd tests/component/subscription-service
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live

`GET /__admin/requests` on the WireMock container showed real HTTP
requests from `subscription-api`'s own container (`User-Agent:
GuzzleHttp/8`), not an in-process fake — the response the test asserts
on genuinely round-tripped over the network. `subscription-outbox`'s
and `subscription-consumer`'s own logs each showed exactly one
`Published 1`/`Consumed 1` line per test run, matching the one
`subscription.created.v1` and one `invoice.paid.v1` the suite produces
and consumes. The whole three-test suite ran in under two seconds —
the cost saving ADR 0004 predicted for Component over Service
integration, from needing only one service's container instead of
two-plus.
