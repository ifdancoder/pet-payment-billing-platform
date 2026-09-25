# KEDA

*[Русская версия](README.ru.md)*

`scaledobjects.yaml` scales the four RabbitMQ consumer Deployments by queue
depth. It references the actual queue names and reads `RABBITMQ_URI` from
`rabbitmq-secret`. These resources are not part of the local overlay because
KEDA is not installed there.

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm upgrade --install keda kedacore/keda --namespace keda --create-namespace
kubectl apply -k infrastructure/kubernetes/platform/keda
```
