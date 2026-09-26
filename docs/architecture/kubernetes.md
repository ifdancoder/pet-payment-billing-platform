# Kubernetes infrastructure

*[Русская версия](kubernetes.ru.md)*

What lives under `infrastructure/kubernetes/` and how it relates to the
architecture in [`overview.md`](overview.md). For actual commands (how to
render/apply things), see
[`infrastructure/kubernetes/platform/README.md`](../../infrastructure/kubernetes/platform/README.md).

## Why this is separate from Docker Compose

`docker-compose.yaml` at the repo root is the complete local environment:
gateway, seven APIs, workers, PostgreSQL and RabbitMQ.
`infrastructure/kubernetes/` targets an actual cluster and isn't part of
local development at all. Keeping them apart means local dev stays fast
and dependency-free while the cluster manifests can evolve toward a real
deployment on their own timeline.

## Layering: base vs platform

- **`base/`**: the `pet-payment-billing-platform` namespace and any
  other cluster-wide primitives everything else depends on.
- **`platform/`**: platform-level workloads inside that namespace, one
  directory per concern:

  | Component | Role | Why |
  | --- | --- | --- |
  | `ingress/` | Routes external traffic to the API gateway | Same job the local Nginx gateway does; ingress-nginx keeps it in the same controller family |
  | `rabbitmq/` | Runs RabbitMQ as a managed cluster (Cluster Operator) with topology as code (Topology Operator) | Keeps queues/exchanges/bindings declarative and owned by the services that need them, once those services exist |
  | `keda/` | Event-driven autoscaling for queue consumers | Scales consumers on RabbitMQ queue depth instead of just CPU/memory |
  | `external-secrets/` | Syncs secrets from an external store into native `Secret` objects | Keeps credentials out of the manifests |
  | `observability/` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | Where every service's traces/metrics/logs land, see `overview.md`'s Observability section |

Application workloads for all seven services live in `base/`, including API,
migration, outbox, consumer, delivery and renewal workloads as appropriate.
`platform/` contains only shared, platform-owned components.

## Current status

- The local overlay runs all seven services plus PostgreSQL and single-node
  RabbitMQ and is exercised by `tests/kind/`.
- Ingress and observability manifests are renderable today. Operator-backed
  RabbitMQ, KEDA and External Secrets remain optional production overlays and
  document their controller prerequisites.

## Related docs

- [`overview.md`](overview.md) for the overall system architecture.
- [`../adr/README.md`](../adr/README.md) for decisions not captured here
  (why ingress-nginx over Traefik, why one namespace for the platform
  layer at this stage, that kind of thing).
