# Платформенный слой (Kubernetes)

*[English version](README.md)*

Платформенная инфраструктура, которая работает внутри кластера рядом с
namespace `pet-payment-billing-platform` из `../base/` и настроена для
него.

Она не подключена к локальной разработке: локально используется
`docker-compose.yaml` в корне репозитория. Этот каталог предназначен
для настоящего Kubernetes-кластера и пока остаётся заготовкой; см.
раздел «Статус» (фаза 0) в корневом README.

## Структура

| Каталог | Назначение | Состояние |
| --- | --- | --- |
| `ingress/` | Ingress-ресурс для API gateway (ingress-nginx) | Готов, контроллер устанавливается отдельно |
| `rabbitmq/` | Кластер RabbitMQ и топология (Cluster/Topology Operators) | Заготовка, см. `rabbitmq/cluster.yaml` |
| `autoscaling/` | HorizontalPodAutoscalers по CPU для API Deployments | Определены, нужен metrics-server |
| `keda/` | Event-driven autoscaling консьюмеров очередей | Не установлен, см. `keda/README.ru.md` |
| `external-secrets/` | Синхронизация секретов из внешнего хранилища | Не установлен, см. `external-secrets/README.ru.md` |
| `observability/` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | Готово |

## Требования

- Kubernetes-кластер и направленный на него контекст `kubectl`.
- [Kustomize](https://kustomize.io/) с включённым рендерингом Helm-
  чартов (встроен в `kubectl` начиная с 1.21 и включается через
  `--enable-helm`, либо устанавливается отдельным бинарником).
- `helm` в `PATH`: Kustomize использует его для рендера чартов из
  `helmCharts:`.
- Применённый namespace `pet-payment-billing-platform` из `../base/`.

## Использование

Чтобы отрендерить и при необходимости применить компонент, например
observability:

```bash
kubectl kustomize --enable-helm infrastructure/kubernetes/platform/observability | kubectl apply -f -
```

Каждый подкаталог рендерится независимо. `ingress/` и `rabbitmq/` не
используют Helm inflator, поэтому для них `--enable-helm` не нужен.
