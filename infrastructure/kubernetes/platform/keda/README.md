# KEDA

[KEDA](https://keda.sh/) handles event-driven autoscaling for the
RabbitMQ consumer Deployments, scaling on queue depth — CPU is a bad
signal for queue backlog, since a consumer can be idle-CPU while a
queue backs up.

## Status

Not applied anywhere yet. KEDA itself isn't installed on the local kind
cluster (no metrics-server either, which the CPU-based HPAs in
`platform/autoscaling` would also need), so this directory's
`ScaledObject`s and `platform/autoscaling`'s `HorizontalPodAutoscaler`s
are both left out of `overlays/local`'s default resources — applying
them there would sit inert and unverifiable. `scaledobjects.yaml`
defines one `ScaledObject` per RabbitMQ-consuming Deployment
(subscription-consumer, billing-consumer, payment-consumer,
notification-ingest-consumer), each targeting its actual queue name and
authenticating via `rabbitmq-secret`'s `RABBITMQ_URI` key.

## Applying it

Requires KEDA installed first:

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm upgrade --install keda kedacore/keda \
  --namespace keda --create-namespace
kubectl apply -k infrastructure/kubernetes/platform/keda
```
