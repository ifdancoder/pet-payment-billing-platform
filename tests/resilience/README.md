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
- [`rabbitmq-outage/`](rabbitmq-outage/) — done, see its own README.
  Stops the broker itself; proves creating a Subscription isn't
  affected at all (the HTTP create flow never resolves `AMQPChannel`),
  then proves both the outbox relay and the consumer recover their own
  connections on their own once RabbitMQ is back.
- [`consumer-crash/`](consumer-crash/) — done, see its own README. The
  fourth and last planned scenario. Kills `billing-consumer` for real,
  timed (via a small, additive, off-by-default delay hook) to land
  between its DB commit and its AMQP ack, so RabbitMQ genuinely
  redelivers the message; proves the restarted consumer's Inbox guard
  stops the redelivery from creating a duplicate Invoice — not a
  simulated duplicate, an actual crash-and-recover.

All four originally planned resilience scenarios are done.
