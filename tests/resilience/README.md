# Resilience tests

Failure-mode scenarios, not business scenarios: duplicate delivery,
a consumer crashing mid-message, a RabbitMQ outage, Outbox recovery
after a crash before publish. Each one proves one specific claim this
platform's architecture makes (Outbox/Inbox, idempotent consumers) can
actually survive the failure it's meant to survive — not just that the
happy path works. See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md).

- [`duplicate-delivery/`](duplicate-delivery/) — done, see its own
  README. The same `event_id` published twice; proves Billing's Inbox
  actually stops the second one from creating a duplicate Invoice, not
  just that the code has a `recordIfNew()` call in it.
- [`outbox-recovery/`](outbox-recovery/) — done, see its own README.
  Stops `billing-outbox` mid-scenario (the test controls Docker
  itself), creates an Invoice while it's down, then proves the missed
  row reaches the wire once it's running again — not lost, not
  replayed from scratch.

Not built yet: `consumer-crash/`, `rabbitmq-outage/`.
