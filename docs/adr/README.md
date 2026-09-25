# Architecture Decision Records

*[Русская версия](README.ru.md)*

ADRs record decisions that are costly to reverse, affect multiple services, or
cannot be inferred safely from the code. A superseded decision gets a new ADR;
the original remains as historical context.

Use a zero-padded `NNNN-short-title.md` filename and these sections:

- Status: Proposed, Accepted, or Superseded by NNNN.
- Context: constraints that require a decision.
- Decision: the chosen behavior or boundary.
- Consequences: costs, limitations, and follow-up work.

English is canonical. Russian translations use the `*.ru.md` suffix.

## Decisions

- [0001. API gateway routing and the public/internal split](0001-api-gateway-routing.md)
- [0002. RabbitMQ messaging contract](0002-rabbitmq-messaging.md)
- [0003. Kubernetes foundation](0003-kubernetes-foundation.md)
- [0004. Testing strategy](0004-testing-strategy.md)
