# Платформенные компоненты Kubernetes

*[English version](README.md)*

Общие компоненты кластера для namespace `pet-payment-billing-platform`.
Ресурсы приложений определены в `../../base`; корневой стек Compose этот каталог
не использует.

| Каталог | Содержимое |
| --- | --- |
| `ingress/` | маршрут ingress-nginx к шлюзу |
| `observability/` | Helm-чарты OpenTelemetry Collector, Prometheus, Grafana, Tempo и Loki |
| `rabbitmq/` | namespace без ресурсов операторов |
| `autoscaling/` | HPA по загрузке CPU |
| `keda/` | ресурсы `ScaledObject` по глубине очередей RabbitMQ |
| `external-secrets/` | инструкция установки; ресурсов пока нет |

Для каталогов Kustomize с Helm нужны `helm` и `--enable-helm`. Ресурсы
операторов также требуют соответствующих CRD и контроллеров.

Перед применением отрендерите компонент:

```bash
kubectl kustomize --enable-helm infrastructure/kubernetes/platform/observability
```

`ingress/` и пустая конфигурация `rabbitmq/` не требуют Helm. Ресурсы операторов
и автомасштабирования не входят в локальный overlay.
