# RabbitMQ topology

*[Русская версия](README.ru.md)*

This is where [RabbitMQ Topology Operator](https://www.rabbitmq.com/kubernetes/operator/install-topology-operator)
resources (`Queue`, `Exchange`, `Binding`, `Vhost`) will go, once:

- the RabbitMQ cluster (`../cluster.yaml`) is actually deployed, and
- application services exist and know what queues/exchanges/bindings
  they need.

Neither of those is true yet (see the root README's "Status"), so
there's nothing to declare here. Expected layout once services land:

```text
topology/
├── kustomization.yaml
├── vhosts.yaml
├── <service-name>-exchanges.yaml
├── <service-name>-queues.yaml
└── <service-name>-bindings.yaml
```
