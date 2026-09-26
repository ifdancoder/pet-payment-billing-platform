# kind platform smoke tests

*[English version](README.md)*

Kubernetes-ориентированный тестовый сьют из
[`ADR 0004`](../../docs/adr/0004-testing-strategy.ru.md):
инфраструктурные вопросы проверяются в Kubernetes, отдельно от
бизнесовых Docker Compose сьютов в `tests/integration/`, `tests/e2e/` и
`tests/resilience/`.

Это самостоятельный Pest-проект, но он не создаёт и не удаляет кластер.
Он работает против текущего контекста `kubectl` и ожидает, что local
overlay уже развёрнут. Такое разделение оставляет подготовку
кластера/образов в существующем Kubernetes workflow; сам тестовый
процесс ничего не меняет, кроме одного явно заявленного rolling restart
`billing-api`.

## Что он доказывает

1. **Маршрутизация Ingress:** все семь семейств публичных путей идут
   через настоящий ingress-nginx и gateway к нужному backend; неизвестный
   путь попадает в явный 503 fallback gateway.
2. **Доступность при rolling update:** настоящий
   `kubectl rollout restart` двухрепличного `billing-api` продолжает
   возвращать 200 через Ingress, пока заменяются оба pod-а.
3. **Бизнесовый smoke:** golden path successful-subscription доходит до
   Active и создаёт payment receipt через настоящий Ingress,
   Kubernetes Services/DNS, все семь сервисов, Postgres и RabbitMQ.

Это намеренно не ещё один полный каталог E2E. Первый и второй тесты
задают вопросы Kubernetes/платформе; третий — маленький бизнес-canary,
доказывающий, что развёрнутая система в целом пригодна к использованию.
NetworkPolicy и autoscaling здесь не заявлены: local overlay их не
применяет, потому что kindnet не исполняет NetworkPolicy, а в кластере
нет metrics-server.

## Запуск

Предварительные условия:

- kind-кластер `pet-payment-billing-platform` существует;
- `kubectl` указывает на него;
- `infrastructure/kubernetes/overlays/local` применён, все workloads
  готовы;
- нативные Secrets созданы из игнорируемого локального `.env` командой
  `make kind-secrets`;
- ingress-nginx опубликован на `localhost:8090`, как задано в
  `infrastructure/kubernetes/kind-cluster.yaml`.

Затем:

```bash
cd tests/kind
composer install
composer test
```

Значения по умолчанию соответствуют local-кластеру. Для другого
доступного кластера или port-forward их можно переопределить, не меняя
тесты:

```bash
GATEWAY_URL=http://localhost:18090 \
GATEWAY_HOST=api.pet-payment-billing-platform.local \
composer test
```

Сьют оставляет общий кластер и тестовые данные на месте. Каждый
бизнесовый идентификатор уникален, поэтому повторные прогоны не
конфликтуют.

## Баг, который нашёл сьют

Первый живой прогон rolling-update стабильно терял запрос, хотя у
`billing-api` были две реплики, `maxUnavailable: 0` и соответствующий
PodDisruptionBudget. Kubernetes может доставить SIGTERM раньше, чем
удаление endpoint успеет распространиться, оставляя короткое окно, в
котором трафик всё ещё направляется в завершающийся процесс.

`infrastructure/kubernetes/overlays/local/api-prestop.yaml` добавляет
пятисекундное окно `preStop` для отвода трафика во все API deployment-ы.
После этого полный сьют прошёл вживую, а rolling-update тест прошёл ещё
раз отдельным повторным прогоном. Концептуально patch не local-only: он
живёт в единственном существующем сегодня overlay и должен быть вынесен
в общий base либо добавлен в каждый будущий environment overlay.
