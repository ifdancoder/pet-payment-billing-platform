# Shared test support

A small Composer library (`billing-platform/test-support`), pulled
into `tests/integration/*/` and `tests/e2e/*/` Pest projects via a
`path` repository — not a service, not shared with any service's own
`vendor/`. See
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
The first two slices still have their own local copies; migrating them
to this package is a follow-up, not done yet.
