# Kubernetes

*[English version](kubernetes.md)*

Ресурсы приложений находятся в `infrastructure/kubernetes/base`. Локальный
overlay запускает семь сервисов, PostgreSQL, один узел RabbitMQ и Nginx в
namespace `pet-payment-billing-platform`. API и фоновые процессы вынесены в
отдельные workload-ресурсы там, где это требуется.

Общие компоненты находятся в `infrastructure/kubernetes/platform`:

| Каталог | Компонент | Локальный overlay |
| --- | --- | --- |
| `ingress` | маршрут ingress-nginx | включён |
| `observability` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | рендерится, по умолчанию не включён |
| `rabbitmq` | RabbitMQ Cluster и Topology Operators | опционально |
| `keda` | масштабирование по глубине очереди RabbitMQ | опционально |
| `external-secrets` | ресурсы External Secrets Operator | опционально |
| `autoscaling` | ресурсы HPA | опционально |

Опциональные overlays требуют установленных контроллеров или Metrics API.
Локальный kind использует базовый RabbitMQ Deployment и не применяет NetworkPolicy, KEDA,
HPA или External Secrets.

Команды рендера и применения приведены в
[README платформенного слоя](../../infrastructure/kubernetes/platform/README.ru.md).
Решения по среде выполнения, проверкам состояния, Kustomize и namespace зафиксированы в
[ADR 0003](../adr/0003-kubernetes-foundation.ru.md).
