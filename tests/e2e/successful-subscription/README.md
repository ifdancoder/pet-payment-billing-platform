# E2E: successful subscription

*[Русская версия](README.ru.md)*

The platform's first end-to-end scenario (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
All seven services, real Postgres, real RabbitMQ, no direct-publish
shortcuts anywhere: Merchant → Customer → Product/Price → Subscription
→ Invoice → Payment → Subscription Active → Notification, walked
entirely through real HTTP with every event on the wire produced by
the actual service whose job that is.

## Not new integration work — composition

Every hop here was already proven in isolation by a
[service integration](../../integration/) slice, each of which
deliberately skips 4-5 services and fakes one upstream event to stay a
focused 2-3-service test:

- [`subscription-to-billing/`](../../integration/subscription-to-billing/) — Subscription → Billing
- [`billing-to-payment/`](../../integration/billing-to-payment/) — Billing → Payment
- [`payment-to-billing/`](../../integration/payment-to-billing/) — Payment → Billing (the `invoice.paid.v1` translation)
- [`billing-to-subscription/`](../../integration/billing-to-subscription/) — Billing → Subscription (activation)

What none of those could prove on their own: that the whole chain
holds together end to end with the system generating and threading
every ID itself, and that Notification — consuming
`payment.succeeded.v1` independently of Billing's own consumption of
the same event — actually fires in parallel with the rest of the
chain, not after it. That's what this test is actually for.

## What's running

All seven services, each with every workload role it has (`api` plus
whichever `consumer`/`outbox` roles apply — see
[`docker-compose.yaml`](docker-compose.yaml)), plus `postgres` and
`rabbitmq`. Identity is included even though nothing downstream
enforces the merchant it creates yet — this scenario's opening step is
still "Merchant" per the platform's own target flow (see the root
README).

## Running it

```bash
cd tests/e2e/successful-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## A real, self-healing race, worth knowing about

`billing-outbox` and `billing-consumer` don't depend on `billing-api`'s
migration finishing — only on `postgres` being healthy, same as every
`docker-compose.yaml` in this repo (see
[`tests/integration/subscription-to-billing/docker-compose.yaml`](../../integration/subscription-to-billing/docker-compose.yaml)
for why migrating inline is fine here). With seven services starting
at once instead of two, this actually showed up once: `billing-outbox`
started polling `outbox_messages` before `billing-api`'s
`migrate --force` had created it yet, logged one `SQLSTATE[42P01]:
Undefined table` error, and its `while true` retry loop caught up a
couple of seconds later — the test still passed cleanly, since
`eventually()` already tolerates exactly this kind of startup jitter.
Left as-is rather than "fixed": it's a one-time cold-start cost with no
observable effect on the test, and a proper fix (each worker either
migrating idempotently itself, or an explicit `depends_on` on the api
container's own readiness) is a Kubernetes-style concern this
disposable, single-replica-per-service compose stack doesn't actually
need — see
[`docs/adr/0003-kubernetes-foundation.md`](../../../docs/adr/0003-kubernetes-foundation.md)
for why Kubernetes solves this differently (a separate migrate Job,
run once, before the Deployment rolls out at all).
