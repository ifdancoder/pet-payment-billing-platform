# Resilience: RabbitMQ outage

*[Русская версия](README.ru.md)*

The third resilience test (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Not a business scenario or a boundary — a specific claim about failure
behavior, and an architectural one rather than a code-path one: does
creating a Subscription survive RabbitMQ being completely unreachable,
and does the rest of the chain catch up on its own once the broker
comes back, with nothing needing to be retried, replayed, or lost.

## What the claim actually rests on

`subscription-api`'s create flow — `CreateSubscriptionHandler` and
everything it depends on (the repository, the two HTTP gateways to
customer/catalog-service, the Outbox port) — never resolves
`AMQPChannel` at all. `AppServiceProvider` registers it as a lazy
Laravel singleton; only `PublishOutboxMessagesCommand` and the
`*-events:consume` commands ever ask the container for one. So an HTTP
request creating a Subscription has no code path to RabbitMQ to fail
on in the first place — this test exists to confirm that's actually
true at runtime, not just true by reading the code.

Reuses [`subscription-to-billing/`](../../integration/subscription-to-billing/)'s
exact stack and create flow — this is a new question about an existing
boundary (does it survive an outage), not a new boundary.

## What's running

| Service | Role |
| --- | --- |
| `postgres` | one instance |
| `rabbitmq` | the thing under test — stopped and restarted by the test itself, not by `docker compose up` choosing not to start it |
| `customer-service` | subscription-api's synchronous dependency |
| `catalog-service` | subscription-api's synchronous dependency |
| `subscription-api` | the write the outage is supposed to not affect |
| `subscription-outbox` | must recover its own connection once RabbitMQ is back, with no help |
| `billing-api` | exposes `GET /invoices` for the test's assertions |
| `billing-consumer` | must also recover its own connection once RabbitMQ is back |

## Running it

```bash
cd tests/resilience/rabbitmq-outage
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Reused `DockerCompose`, from `tests/support/`

This was originally this test's own second local copy of the one
written for [`outbox-recovery/`](../outbox-recovery/) — per the
extraction discipline used throughout `tests/support/`'s own history
(copy for the first two callers, share on the third), it moved into
[`DockerCompose`](../../support/src/DockerCompose.php) once
[`tests/resilience/consumer-crash/`](../consumer-crash/) became the
third caller, gaining a `kill(service, signal)` method neither of the
first two copies needed.

## Verified live: real connection failures, not a lucky race window

Both `subscription-outbox` and `billing-consumer` logged genuine
`stream_socket_client(): Unable to connect to tcp://rabbitmq:5672
(Connection refused)` errors — several of them each, from their own
independent retry loops — while `rabbitmq` was actually down, not a
test that happened to finish before either worker's next attempt. Once
the broker came back, both immediately succeeded on their very next
loop iteration: `subscription-outbox` logged `Published 1 outbox
message(s).`, `billing-consumer` logged `Consumed 1 message(s).` —
exactly the row that had been waiting.
