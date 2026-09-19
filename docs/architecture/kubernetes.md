# Kubernetes infrastructure

What lives under `infrastructure/kubernetes/` and how it relates to the
architecture in [`overview.md`](overview.md). For actual commands (how to
render/apply things), see
[`infrastructure/kubernetes/platform/README.md`](../../infrastructure/kubernetes/platform/README.md).

## Why this is separate from Docker Compose

`docker-compose.yaml` at the repo root is the local dev environment: the
gateway, PostgreSQL and RabbitMQ containers you develop against.
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

Application services aren't part of the Kubernetes manifests yet, even
though all seven now exist under `services/` — they're only dockerized
and deployed once the roadmap actually reaches that phase (see the root
README, "Status"). Each service gets its own deployment manifests then;
`platform/` only covers the shared, platform-owned pieces.

## Current status

- `ingress/` and `observability/` have manifests you can actually render
  and apply today.
- `rabbitmq/`, `keda/` and `external-secrets/` are placeholders. The
  operators/controllers they need aren't installed, and there's nothing
  downstream yet (no services, no queues, no secrets to sync). Each one
  documents its own plan for later.

The Kubernetes layer is still scaffolded ahead of the services it'll
eventually host — the platform-level manifests exist, but nothing here
is deployed anywhere yet.

## Related docs

- [`overview.md`](overview.md) for the overall system architecture.
- [`../adr/README.md`](../adr/README.md) for decisions not captured here
  (why ingress-nginx over Traefik, why one namespace for the platform
  layer at this stage, that kind of thing).
