# KEDA

[KEDA](https://keda.sh/) will handle event-driven autoscaling for
application services, mainly based on RabbitMQ queue length.

## Status

Not installed. There's nothing to scale yet, no consumers exist (see the
root README's "Status").

## Planned setup

Install via Helm:

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm install keda kedacore/keda --namespace keda --create-namespace
```

Then define a `ScaledObject` per queue-consuming service once that
service actually exists, scaling on the `rabbitmq` trigger (queue length
or message rate).

Nothing else in this directory, just this README for now.
