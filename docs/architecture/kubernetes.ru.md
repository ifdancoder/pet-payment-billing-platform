# Инфраструктура Kubernetes

*[English version](kubernetes.md)*

Что находится в `infrastructure/kubernetes/` и как это связано с
архитектурой из [`overview.ru.md`](overview.ru.md). Команды для рендера
и применения конфигурации приведены в
[`infrastructure/kubernetes/platform/README.ru.md`](../../infrastructure/kubernetes/platform/README.ru.md).

## Почему это отделено от Docker Compose

`docker-compose.yaml` в корне репозитория — полное локальное окружение:
gateway, семь API, workers, PostgreSQL и RabbitMQ. Каталог
`infrastructure/kubernetes/` предназначен для настоящего кластера и
вообще не участвует в локальной разработке. Благодаря разделению
локальная среда остаётся быстрой и не требует лишних зависимостей, а
кластерные манифесты могут независимо развиваться в сторону реального
развёртывания.

## Слои: base и platform

- **`base/`**: namespace `pet-payment-billing-platform` и другие
  общекластерные примитивы, от которых зависит всё остальное.
- **`platform/`**: платформенные workloads внутри этого namespace, по
  одному каталогу на каждую задачу:

  | Компонент | Роль | Зачем |
  | --- | --- | --- |
  | `ingress/` | Направляет внешний трафик в API gateway | Выполняет ту же работу, что локальный Nginx gateway; ingress-nginx сохраняет единое семейство контроллеров |
  | `rabbitmq/` | Запускает RabbitMQ как управляемый кластер (Cluster Operator), а топологию хранит как код (Topology Operator) | Делает очереди, exchanges и bindings декларативными и принадлежащими сервисам, которым они нужны |
  | `keda/` | Event-driven autoscaling консьюмеров очередей | Масштабирует консьюмеры по глубине очереди RabbitMQ, а не только по CPU/памяти |
  | `external-secrets/` | Синхронизирует секреты из внешнего хранилища в нативные объекты `Secret` | Не допускает попадания учётных данных в манифесты |
  | `observability/` | OpenTelemetry Collector, Prometheus, Grafana, Tempo, Loki | Место назначения traces, metrics и logs всех сервисов; см. раздел Observability в `overview.ru.md` |

Workloads всех семи сервисов находятся в `base/`: API, migration, outbox,
consumer, delivery и renewal — где это нужно. `platform/` содержит только
общие компоненты, которыми владеет платформа.

## Текущее состояние

- Local overlay запускает все семь сервисов, PostgreSQL и single-node
  RabbitMQ и проверяется `tests/kind/`.
- Ingress и observability можно рендерить уже сейчас. Operator-backed
  RabbitMQ, KEDA и External Secrets остаются опциональными production
  overlays и документируют необходимые controllers.

## Связанные документы

- [`overview.ru.md`](overview.ru.md) — общая архитектура системы.
- [`../adr/README.ru.md`](../adr/README.ru.md) — решения, не описанные
  здесь (например, почему выбран ingress-nginx, а не Traefik, и почему
  на данном этапе используется один namespace для платформенного слоя).
