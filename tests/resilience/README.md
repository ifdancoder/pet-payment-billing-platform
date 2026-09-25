# Resilience tests

*[Русская версия](README.ru.md)*

Each suite isolates one failure mode in the messaging path.

- `duplicate-delivery/`: inbox deduplication prevents a second invoice.
- `outbox-recovery/`: a restarted relay publishes rows committed while it was stopped.
- `rabbitmq-outage/`: an API write succeeds while RabbitMQ is down and catches up after recovery.
- `consumer-crash/`: a crash after commit and before acknowledgement redelivers without duplicating effects.
