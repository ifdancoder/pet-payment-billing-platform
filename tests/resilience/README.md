# Resilience tests

Failure-mode scenarios, not business scenarios: duplicate delivery,
a consumer crashing mid-message, a RabbitMQ outage, Outbox recovery
after a crash before publish. Each one proves one specific claim this
platform's architecture makes (Outbox/Inbox, idempotent consumers) can
actually survive the failure it's meant to survive — not just that the
happy path works. Not built yet; needs the
[service integration slices](../integration/) they build on to exist
first. See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md).
