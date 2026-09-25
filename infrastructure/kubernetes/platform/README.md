# Platform layer (Kubernetes)

*[Русская версия](README.ru.md)*

Platform-level infrastructure that runs inside the cluster, alongside
(and configured for) the `pet-payment-billing-platform` namespace defined
in `../base/`.

This isn't wired into local development. Local dev uses
`docker-compose.yaml` at the repo root. This directory targets a real
Kubernetes cluster and right now it's just a scaffold, see the root
README's "Status" section (Phase 0).

## Layout

| Directory | Purpose | Status |
| --- | --- | --- |
| `ingress/` | Ingress resource for the API gateway (ingress-nginx) | Ready, controller installed separately |
| `rabbitmq/` | RabbitMQ cluster + topology (Cluster/Topology Operators) | Placeholder, see `rabbitmq/cluster.yaml` |
| `autoscaling/` | HorizontalPodAutoscalers (CPU) for API Deployments | Defined, needs metrics-server |
| `keda/` | Event-driven autoscaling for queue consumers | Not installed, see `keda/README.md` |
| `external-secrets/` | Secret sync from an external store | Not installed, see `external-secrets/README.md` |
| `observability/` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | Ready |

## Prerequisites

- A Kubernetes cluster and a `kubectl` context pointed at it.
- [Kustomize](https://kustomize.io/) with Helm chart inflation enabled
  (built into `kubectl` since 1.21+ via `--enable-helm`, or the
  standalone `kustomize` binary).
- `helm` on `PATH` (the Kustomize Helm inflator uses it to render charts
  referenced from `helmCharts:`).
- The `pet-payment-billing-platform` namespace applied from `../base/`.

## Usage

Render (and optionally apply) a component, e.g. observability:

```bash
kubectl kustomize --enable-helm infrastructure/kubernetes/platform/observability | kubectl apply -f -
```

Each subdirectory renders independently. `ingress/` and `rabbitmq/` don't
use the Helm inflator, so they work fine without `--enable-helm`.
