# E2E: failed payment

*[Русская версия](README.ru.md)*

The platform's second full end-to-end scenario, and its first
failure-path one (see
[`docs/architecture/testing-strategy.md`](../../../docs/architecture/testing-strategy.md)).
Same seven real services, same real-HTTP-only chain as
[`successful-subscription/`](../successful-subscription/) — the only
difference is the Price's amount, which is set to
`FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS` (`66660000`,
any currency), so the charge is guaranteed to decline rather than
succeed.

## Why this needed a decline-trigger amount, not a decline-trigger field

`ChargeRequest`, the boundary DTO `IPaymentGatewayPort::charge()`
takes, carries only an attempt id and an amount — no card/token
concept a test could set to "always decline". Adding one would mean
threading a "how should this fail" concept through Subscription and
Billing too, just so it could reach a gateway three hops downstream,
for a purely test-only purpose. The amount, on the other hand, is
already the one value this scenario's `POST .../prices` call sets that
survives, completely unmodified, all the way down to the
`ChargeRequest` `ProcessPaymentHandler` builds — so
[`FakePaymentGateway`](../../../services/payment-service/app/Infrastructure/Payment/Adapters/PaymentGateway/Fake/FakePaymentGateway.php)
declines one specific, deliberately unmistakable amount
(`66660000` minor units — $666,600.00 in any currency) instead. No
real price is ever going to land on it by accident, and it needed no
change outside `FakePaymentGateway` itself.

## What this proves

Not a new event boundary — every hop here (`subscription.created.v1`,
`invoice.created.v1`, `payment.failed.v1`, `invoice.payment_failed.v1`)
is already covered by its own
[`tests/integration/`](../../integration/) slice. What only a full E2E
run can show is where the whole chain *actually* leaves things after a
real decline, produced by the real gateway boundary, not a
direct-published stand-in:

- The Invoice stays **Open** — `MarkInvoicePaidHandler` is simply never
  reached, since `payment.succeeded.v1` never exists to trigger it.
- The Payment itself is **Failed**, with `attempts[0].failure_code`
  `card_declined` — the fact a real caller of this API would see.
- The Subscription — which never reached Active in the first place —
  stays **Pending**, not PastDue.
  `HandleInvoicePaymentFailedHandler`'s own guard (already proven in
  isolation by
  [`tests/integration/billing-to-subscription/`](../../integration/billing-to-subscription/))
  only transitions Active → PastDue; a first-ever failed payment before
  activation isn't that case.
- Notification never produces anything at all for this customer — it
  only ever consumes `payment.succeeded.v1`, which this scenario never
  produces.

## Proving those are absences, not "not yet"

`eventually()` waits for a condition to become true; it can't confirm
one stays false. Once the Payment is confirmed Failed, the test can't
poll its way to proving the Invoice and Subscription never change —
so, same documented exception as
[`tests/integration/payment-to-notification/`](../../integration/payment-to-notification/)
and
[`tests/resilience/consumer-crash/`](../../resilience/consumer-crash/),
it gives the downstream reaction (`payment.failed.v1` →
`billing-consumer` relays `invoice.payment_failed.v1` →
`subscription-consumer`'s guard) a real, fixed window — several actual
worker loop iterations — before asserting both stayed exactly where
they started.

## Running it

```bash
cd tests/e2e/failed-payment
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Verified live

`billing-consumer` consumed exactly 2 messages for this run
(`subscription.created.v1`, then `payment.failed.v1`) —
`payment-outbox`'s own logs show exactly 1 message published
(`payment.failed.v1`), and `subscription-consumer` consumed exactly 1
(`invoice.payment_failed.v1`). `notification-ingest-consumer` consumed
**0** — direct confirmation it never saw anything, not just that
nothing showed up in `GET /notifications`.
