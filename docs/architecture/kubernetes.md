# Kubernetes

*[Русская версия](kubernetes.ru.md)*

Application resources live under `infrastructure/kubernetes/base`. The local
overlay runs all seven services, PostgreSQL, single-node RabbitMQ, and the Nginx
gateway in the `pet-payment-billing-platform` namespace. API, consumer, outbox,
delivery, migration, and renewal workloads are separate where required.

Shared components live under `infrastructure/kubernetes/platform`:

| Directory | Component | Local overlay |
| --- | --- | --- |
| `ingress` | ingress-nginx route | enabled |
| `observability` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | renderable, not enabled by default |
| `rabbitmq` | RabbitMQ Cluster and Topology Operators | optional |
| `keda` | RabbitMQ queue-depth scaling | optional |
| `external-secrets` | External Secrets Operator resources | optional |
| `autoscaling` | HPA resources | optional |

The optional overlays require their controllers or metrics APIs to be installed.
Local kind uses the base RabbitMQ Deployment and does not apply NetworkPolicy,
KEDA, HPA, or External Secrets resources.

See [the platform README](../../infrastructure/kubernetes/platform/README.md)
for render and apply commands. [ADR 0003](../adr/0003-kubernetes-foundation.md)
records the runtime, health-check, Kustomize, and namespace decisions.
