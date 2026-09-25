# KEDA

*[English version](README.md)*

`scaledobjects.yaml` масштабирует четыре RabbitMQ consumer Deployments по
глубине очередей. Ресурсы используют фактические имена очередей и читают
`RABBITMQ_URI` из `rabbitmq-secret`. Они не входят в local overlay, потому что
KEDA там не установлена.

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm upgrade --install keda kedacore/keda --namespace keda --create-namespace
kubectl apply -k infrastructure/kubernetes/platform/keda
```
