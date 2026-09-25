# KEDA

*[English version](README.md)*

[KEDA](https://keda.sh/) обеспечивает event-driven autoscaling
Deployment-ов консьюмеров RabbitMQ по глубине очереди. CPU — плохой
сигнал для backlog очереди: консьюмер может почти не использовать CPU,
пока очередь продолжает расти.

## Состояние

Пока нигде не применяется. Сама KEDA не установлена в локальном kind-
кластере; там также нет metrics-server, необходимого CPU-based HPA из
`platform/autoscaling`. Поэтому `ScaledObject` из этого каталога и
`HorizontalPodAutoscaler` из `platform/autoscaling` не входят в
стандартные ресурсы `overlays/local`: там они были бы неактивны и их
работу нельзя было бы проверить. `scaledobjects.yaml` определяет по
одному `ScaledObject` для каждого RabbitMQ-consuming Deployment
(`subscription-consumer`, `billing-consumer`, `payment-consumer`,
`notification-ingest-consumer`), указывает фактическое имя его очереди
и аутентифицируется через ключ `RABBITMQ_URI` секрета `rabbitmq-secret`.

## Применение

Сначала необходимо установить KEDA:

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm upgrade --install keda kedacore/keda \
  --namespace keda --create-namespace
kubectl apply -k infrastructure/kubernetes/platform/keda
```
