# RabbitMQ topology

*[Русская версия](README.ru.md)*

The local overlay and root Compose stack declare queues and bindings from the
consumer processes. This directory is reserved for RabbitMQ Topology Operator
resources in shared environments.

No `Queue`, `Exchange`, `Binding`, or `Vhost` resources are defined yet. Add
them only when the operator-backed `RabbitmqCluster` replaces the base RabbitMQ
Deployment; keep names consistent with ADR 0002 and the consumer commands.
