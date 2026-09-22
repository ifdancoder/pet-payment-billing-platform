# Shared test support

A small Composer library (`billing-platform/test-support`), pulled
into `tests/integration/*/`, `tests/e2e/*/` and `tests/resilience/*/`
Pest projects via a `path` repository — not a service, not shared with
any service's own `vendor/`. See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md).

## What's in it

- **`eventually()`** — polls an assertion until it passes or a timeout
  elapses, for asserting on an async chain (HTTP call → Outbox →
  RabbitMQ → a separate consumer process) without a fixed `sleep()`.
- **`AmqpTestClient`** — a generic test-side client for the
  `billing.events` exchange: publish an event directly (standing in for
  an upstream service's own Outbox, when pulling that whole service
  into a stack would make the test 3+ services for no reason),
  declare/bind a real consumer's queue before publishing to it (closes
  a real race — a topic exchange drops a message published before any
  queue is bound to its routing key), or bind a private, throwaway
  queue to assert some other service actually republished something.
  `publish()` takes an optional explicit `eventId` — added for
  [`tests/resilience/duplicate-delivery/`](../resilience/duplicate-delivery/),
  which needs to publish the *same* `event_id` twice to simulate a real
  at-least-once redelivery, not two independent events that happen to
  look alike.
- **`DockerCompose`** — a thin `docker compose stop/start/kill` wrapper
  for tests that control a service's own container mid-scenario
  (stopping a worker to prove it recovers, or killing one at a precise
  moment). `kill(service, signal)` was added for
  [`tests/resilience/consumer-crash/`](../resilience/consumer-crash/),
  the first test needing a real `SIGKILL` rather than a graceful
  stop/start.

## Using it in a Pest project

```json
{
    "repositories": [{"type": "path", "url": "../../support"}],
    "require": {
        "billing-platform/test-support": "@dev"
    }
}
```

`@dev`, not `*` — a `path` repository package with no tag resolves as
`dev-main`, which `composer install` refuses against the default
`minimum-stability: stable` otherwise.

## Extraction history

Not built speculatively — `eventually()` was copied into each of the
first two slices' own `tests/Support/` first
([`subscription-to-billing/`](../integration/subscription-to-billing/),
[`billing-to-payment/`](../integration/billing-to-payment/)), and only
moved here once a third slice
([`payment-to-billing/`](../integration/payment-to-billing/)) needed
it — see the extraction rule in
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md).
The first two slices' own local copies have since been migrated onto
this package too, so every consumer now shares the one implementation.

`DockerCompose` followed the same discipline: copied into
[`outbox-recovery/`](../resilience/outbox-recovery/), then
[`rabbitmq-outage/`](../resilience/rabbitmq-outage/), and only moved
here once a third test
([`consumer-crash/`](../resilience/consumer-crash/)) needed it — which
is also where it gained `kill()`, since neither earlier copy needed
anything beyond `stop()`/`start()`. Those first two copies have since
been migrated here as well.
