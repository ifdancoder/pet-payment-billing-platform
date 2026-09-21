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
