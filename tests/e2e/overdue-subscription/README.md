# E2E: overdue subscription

*[Russian version](README.ru.md)*

The third full end-to-end scenario, and the production recurring-billing
feature that was required to make it honest. One subscription first
completes its initial cycle and becomes Active; the scheduler then opens
the next cycle's Invoice, that renewal charge is declined, and the real
event chain moves the subscription to PastDue.

Nothing is published directly and no database is edited by the test.
The test invokes the same `subscriptions:renew` command that the
Kubernetes `subscription-renewals` CronJob runs, using its operational
`--as-of` option to cross a one-day period boundary without sleeping for
a day.

## Production path under test

1. Subscription stores `current_period_start`, `current_period_end`, and
   an atomic `renewal_pending` guard.
2. `subscriptions:renew` locks a bounded batch of due Active
   subscriptions, advances exactly one period, and writes
   `subscription.renewal_due.v1` to the Outbox in the same transaction.
3. Billing consumes that event and creates the next cycle's Invoice.
4. `invoice.created.v1` carries `billing_reason=subscription_cycle` to
   Payment.
5. The fake provider's reserved renewal-only amount succeeds for
   `subscription_create` but declines `subscription_cycle`.
6. Payment → Billing → Subscription produces
   `payment.failed.v1` → `invoice.payment_failed.v1` → PastDue.

The final scheduler invocation moves the clock to 2030 and still queues
zero renewals, proving a PastDue subscription cannot create a third
Invoice. The pending guard separately prevents overlapping scheduler
runs from opening the same cycle twice.

## Stack and running

The Compose file uses Compose `include` to reuse the exact topology from
`../successful-subscription/docker-compose.yaml`; this scenario does not
maintain a third copy of the same seven-service stack.

```bash
cd tests/e2e/overdue-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live

The final rebuilt-image run passed with 176 assertions in 12.27 seconds.
Container logs showed both real `invoice.created.v1` deliveries, one successful initial
payment, one failed renewal payment, Billing's failure relay, and both
Subscription-side outcomes. No shortcut API or direct AMQP publish was
used.
