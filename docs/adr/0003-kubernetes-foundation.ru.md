# 3. Основа Kubernetes: runtime, манифесты и health checks

*[English version](0003-kubernetes-foundation.md)*

## Статус

Принято

## Контекст

Ни у одного из семи сервисов пока нет Docker-образа или Kubernetes-
манифеста. Прежде чем создавать более двадцати Deployments для семи
сервисов, небольшой набор общих решений нужно один раз осознанно
проверить на настоящем рабочем примере, а не принимать заново при
контейнеризации каждого сервиса. Это тот же принцип «решить один раз, а
не переоткрывать семь раз», что использован для messaging в ADR 0002.

Первый рабочий пример — customer-service: самый простой сервис с одним
Deployment, без consumer, worker и CronJob. На нём паттерн проверяется,
после чего остальные сервисы могут его повторить.

Важно: **один Docker-образ на сервис не означает один Kubernetes Pod на
сервис.** `billing-service:<sha>` — единый образ, но он запускается как
API Deployment, Consumer Deployment и Outbox Deployment, каждый со
своей командой контейнера. Только customer-service и catalog-service,
где нет consumer и outbox, пока достаточно одного Deployment.

## Решение

### Runtime: FrankenPHP в classic mode, без Octane

Контейнер каждого сервиса запускает [FrankenPHP](https://frankenphp.dev/)
(`dunglas/frankenphp:1-php8.5-alpine`) вместо традиционной пары php-fpm
+ nginx. Это один процесс и один бинарник со встроенным корректным
завершением работы: проверено, что `SIGTERM` приводит к чистому выходу в
пределах grace period контейнера с `exit_code: 0` и без оставшихся
соединений; дополнительный shutdown-код не нужен.

Используется classic mode
(`frankenphp php-server --root /app/public`) — модель с одним запросом
на процесс, как в php-fpm, без постоянного состояния приложения между
запросами. **Не** Octane/worker mode: он сохраняет Laravel-приложение в
памяти между запросами ради пропускной способности, поэтому контейнеры
сервисов и singletons, рассчитанные на короткий запрос, могут протекать
состоянием. Кодовая база на это не проверялась. Worker mode остаётся
будущей оптимизацией после проверки classic mode, а не условием запуска
первого сервиса.

### Сборка образа: multi-stage с бинарником из `composer:2` в `base`

Composer-зависимости устанавливаются в stage, построенном `FROM base`
— на том же образе FrankenPHP, что и runtime stage, — а не в официальном
образе `composer:2`. В `composer:2` не скомпилирован `ext-sockets`, без
которого platform check пакета `php-amqplib` завершается ошибкой.
Решение: `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` в
`base`. Тогда один набор extensions используется и при разрешении
графа зависимостей, и в runtime, вместо двух разных PHP-сборок с разными
представлениями о доступных extensions.

Процесс работает от непривилегированного пользователя `laravel`
(uid 1000), которому принадлежат `storage/`, `bootstrap/cache/`
(записываемые пути Laravel), `/data` и `/config` (каталоги состояния
FrankenPHP/Caddy). Последние пока остаются root-owned, из-за чего при
каждом старте появляются безвредные permission-denied warnings; это
работает, но настройка non-root ещё не идеальна.

### Health checks: три endpoint для трёх разных вопросов

`routes/health.php` загружается вне `app/Presentation`: это concern
framework/infra, подобный собственному `/up` Laravel, а не версионируемый
бизнес-маршрут.

| Endpoint | Что проверяет | Kubernetes probe |
| --- | --- | --- |
| `/health/startup` | Только «процесс отвечает по HTTP» | `startupProbe` |
| `/health/live` | То же, без внешних зависимостей | `livenessProbe` |
| `/health/ready` | `DB::connection()->getPdo()` | `readinessProbe` |

Liveness намеренно **не** проверяет базу данных. Ошибка liveness probe
говорит Kubernetes «убей и перезапусти контейнер». Если связать её с
доступностью PostgreSQL, сбой базы заставит Kubernetes циклически
перезапускать каждый pod каждого сервиса, не исправляя причину и
добавляя restart storm. Ошибка readiness говорит «не направляй сюда
трафик» — правильная реакция на невозможность выполнять полезную работу:
рестарт не нужен, трафик идёт в доступный pod или клиент прямо видит
сбой.

### Манифесты: Kustomize, а не Helm, для application workloads

`infrastructure/kubernetes/base/<service>/` содержит базовые манифесты
сервиса (`deployment.yaml`, `service.yaml`, `configmap.yaml`,
`secret.yaml`, `kustomization.yaml`). Каталоги
`infrastructure/kubernetes/overlays/{local,staging,production}/`
накладывают environment-specific значения — число реплик, ресурсы и
тег образа — через Kustomize patches. Не появляется язык шаблонов и
values schema, которую пришлось бы сопровождать параллельно с самими
базовыми манифестами.

Helm остаётся для сторонней инфраструктуры, которую репозиторий не
разрабатывает: это уже верно для RabbitMQ, Prometheus, Grafana, Loki и
Tempo в `platform/`, где собственные `kustomization.yaml` оборачивают
upstream Helm charts. Создаваемые нами application manifests не
получают второй параллельный формат упаковки.

### Namespace: на environment, а не на сервис

Один namespace на environment (`pet-payment-billing-platform` локально,
в будущем `billing-staging` и `billing-production`), а не один на
сервис. Семь сервисов × три окружения × отдельные RBAC, NetworkPolicy и
quota — операционная церемония без пользы для этой платформы. Внутри
единого namespace сервисы различаются labels
`app.kubernetes.io/name`, как и ожидают selectors `kubectl`, Prometheus
и NetworkPolicy.

## Последствия

**Проще:**

- Dockerfile любого следующего сервиса можно получить копированием
  customer-service без изменений для API-образа либо со сменой `CMD`
  для worker/outbox того же сервиса.
- Разделение health checks превращает сбой базы в корректную деградацию
  — pods перестают принимать трафик, — а не в катастрофический цикл
  перезапуска всех pods, пока база недоступна.
- Kustomize overlays хранят отличия окружений в одном месте вместо
  дублированных YAML для каждого environment.

**Сложнее / дальнейшая работа:**

- `/data` и `/config` внутри контейнера остаются root-owned. Это
  косметический недостаток с warnings, но без ошибок; его стоит
  исправить позже, он не блокирует текущий milestone.
- Classic mode уступает Octane/worker mode в throughput; вернуться к
  вопросу следует, когда load testing покажет реальную необходимость.
- Образы ещё не сканируются, не подписываются и не фиксируются по
  digest, используются только tags. Это относится к фазе 11 (CI/CD).
- Решения ADR проверены только на customer-service. Следующая настоящая
  проверка — полный RabbitMQ vertical slice в кластере:
  Subscription → Outbox → RabbitMQ → Billing → Inbox → Invoice →
  Outbox. Именно там вместе проверяются Deployment, worker, CronJob,
  graceful shutdown consumer и migration Jobs, а не только одиночный
  stateless API.
