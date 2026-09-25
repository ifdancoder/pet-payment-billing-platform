# Kubernetes platform components

*[Русская версия](README.ru.md)*

Shared cluster components for the `pet-payment-billing-platform` namespace.
Application workloads are defined under `../../base`; the root Compose stack
does not use this directory.

| Directory | Contents |
| --- | --- |
| `ingress/` | ingress-nginx route to the gateway |
| `observability/` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, and Loki charts |
| `rabbitmq/` | Namespace placeholder; no operator resources yet |
| `autoscaling/` | CPU-based HPAs |
| `keda/` | RabbitMQ queue-depth `ScaledObject` resources |
| `external-secrets/` | Installation notes; no resources yet |

Helm-backed Kustomize directories require `helm` and `--enable-helm`. Operator
resources also require their CRDs and controllers.

Render a component before applying it:

```bash
kubectl kustomize --enable-helm infrastructure/kubernetes/platform/observability
```

`ingress/` and the current empty `rabbitmq/` kustomization do not require Helm.
Optional operator and autoscaling resources are not included in the local
overlay.
