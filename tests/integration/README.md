# Service integration tests

2-3 real services talking through a real RabbitMQ and real databases —
one directory per boundary, each its own standalone Docker Compose
stack and Pest project. Not the whole platform (that's
[`tests/e2e/`](../e2e/)) and not one service in isolation (that's each
service's own `tests/`). See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md)
for the full pyramid and current status of every slice.

- [`subscription-to-billing/`](subscription-to-billing/) — done, see
  its own README.
- [`billing-to-payment/`](billing-to-payment/) — done, see its own
  README.
- [`payment-to-billing/`](payment-to-billing/) — done, see its own
  README. The first slice to use [`../support/`](../support/) instead
  of a local copy of `eventually()`.
- [`billing-to-subscription/`](billing-to-subscription/) — done, see
  its own README. Completed the last slice `tests/e2e/` needed before
  its first scenario could be assembled.
- [`payment-to-notification/`](payment-to-notification/) — done, see
  its own README. The fifth and last of the event-boundary slices
  identified in the pyramid; includes a negative test proving an
  absence (no notification for an unknown customer), not just a
  transition.
