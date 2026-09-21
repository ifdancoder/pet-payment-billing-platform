# Billing Platform

*[English version](README.md)*

Платформа для биллинга и подписок, построенная как набор независимо
разворачиваемых PHP/Laravel сервисов.

Это проект для отработки распределённых биллинговых систем на
практике: Clean Architecture, Hexagonal Architecture, DDD,
event-driven взаимодействие между сервисами.

> Все семь сервисов построены. Сейчас они превращаются в настоящую
> production-style платформу: gateway, messaging topology, надёжность,
> observability, Docker, Kubernetes, CI/CD, затем end-to-end/load
> тестирование. См. «Статус» ниже.

## Цели

Что должна делать платформа:

- управление клиентами
- каталоги продуктов и цен
- подписки и recurring billing
- генерация инвойсов
- обработка платежей и возвраты
- интеграции с платёжными провайдерами
- асинхронные уведомления

И надёжность, неотделимая от любой распределённой системы:

- каждый сервис владеет своими данными
- eventual consistency
- идемпотентность (ключи, идемпотентные консьюмеры/inbox)
- transactional outbox
- retries и dead-letter queues
- distributed tracing и observability

## Архитектура

Микросервисы, каждый — независимо исполняемое Laravel-приложение со
своей базой данных.

Сервисы:

| Сервис | Ответственность |
| --- | --- |
| Identity Service | Аутентификация и авторизация мерчантов |
| Customer Service | Клиенты и их billing-профили |
| Catalog Service | Продукты, цены и скидки |
| Subscription Service | Жизненный цикл подписки |
| Billing Service | Инвойсы и billing-циклы |
| Payment Service | Платежи, возвраты и платёжные провайдеры |
| Notification Service | Асинхронные уведомления клиентов |

Сервисы общаются друг с другом по HTTP, когда нужен ответ сразу же, и
через события RabbitMQ для всего остального (большинство
межсервисных workflow).

## Структура репозитория

```text
pet-payment-billing-platform/
├── services/
├── packages/
├── infrastructure/
│   ├── nginx/
│   ├── postgres/
│   ├── rabbitmq/
│   └── kubernetes/
│       ├── base/
│       └── platform/
├── docs/
│   ├── architecture/
│   └── adr/
├── scripts/
├── tests/
│   ├── integration/
│   ├── e2e/
│   └── resilience/
├── docker-compose.yaml
└── Makefile
```

### services

Все семь Laravel-сервисов. Каждый независимо запускаем и со своей
базой данных. У каждого теперь также есть Dockerfile, и каждый
работает в кластере `kind` под `infrastructure/kubernetes/`; ни один
пока не подключён к локальному `docker-compose.yaml` (см. «Статус»).

### packages

Только общие технические контракты — определения протоколов, клиентские
SDK. Доменные модели остаются внутри каждого сервиса, никогда не
шарятся.

### infrastructure

Конфигурация локальной и платформенной инфраструктуры.

Локальная разработка (docker-compose) поднимает Nginx, PostgreSQL и
RabbitMQ.

`infrastructure/kubernetes/` — отдельный, ориентированный на кластер
слой:

- `base/` настраивает namespace и другие общекластерные вещи
- `platform/` покрывает ingress, RabbitMQ, KEDA, External Secrets и
  observability-стек (OpenTelemetry Collector, Prometheus, Grafana,
  Tempo, Loki). Подробности в
  `infrastructure/kubernetes/platform/README.md`.

Ничего из этого пока не подключено к `make up`. Это отдельный трек от
локальной разработки.

### docs

Заметки по архитектуре и ADR. Английский — канонический язык; там, где
есть русский перевод, он лежит рядом как `*.ru.md`.

### tests

Межсервисные тесты, не принадлежащие ни одному конкретному сервису —
см.
[`docs/architecture/testing-strategy.ru.md`](docs/architecture/testing-strategy.ru.md)
про полную пирамиду (собственные `tests/Unit`, `Integration` и
`Feature` каждого сервиса покрывают всё, что ниже этого уровня):

- `integration/` — 2-3 реальных сервиса через реальный RabbitMQ,
  каждый — свой Docker Compose стек + самостоятельный Pest-проект.
- `e2e/` — полные бизнес-флоу через все сервисы, fake-провайдеры
  платежей/email.
- `resilience/` — failure-сценарии (повторная доставка, падение
  консьюмера, недоступность брокера), не бизнес-сценарии.

## Принципы

### Владение сервисом

Каждый сервис владеет своей логикой и своими данными. Ни один сервис не
лезет напрямую в базу данных другого.

### База данных на сервис

Данные каждого сервиса логически изолированы, даже если локально все
они сидят на одном инстансе PostgreSQL ради удобства. Владение остаётся
раздельным независимо от того, где физически лежат байты.

### Clean Architecture

Бизнес-правила ничего не знают о Laravel, Eloquent, RabbitMQ,
PostgreSQL или платёжных провайдерах. Зависимости направлены внутрь, к
домену.

### Hexagonal Architecture

Всё внешнее (базы данных, платёжные провайдеры, брокеры сообщений,
другие API) проходит через ports и adapters, а не вызывается напрямую
из бизнес-логики.

### Domain-Driven Design

Используется там, где это реально окупается, а не насильно
натягивается на каждый угол системы.

### Event-driven взаимодействие

Сервисы объявляют об изменениях состояния как события через RabbitMQ,
вместо того чтобы вызывать друг друга напрямую.

## Локальная разработка

### Требования

- Docker
- Docker Compose
- GNU Make

### Настройка

Скопировать env-файл:

```bash
make init
```

Запустить всё:

```bash
make up
```

Посмотреть, что запущено:

```bash
make ps
```

Проверить gateway:

```bash
make health
```

Остановить всё:

```bash
make down
```

Удалить контейнеры и локальные volumes:

```bash
make clean
```

## Локальные адреса

| Компонент | Адрес |
| --- | --- |
| API Gateway | `http://localhost:8080` |
| Gateway Health | `http://localhost:8080/health` |
| PostgreSQL | `localhost:5432` |
| RabbitMQ | `localhost:5672` |
| RabbitMQ Management | `http://localhost:15672` |

Учётные данные — в `.env`.

Публичная таблица роутинга gateway (`/v1/...` → каждый сервис, см.
[ADR 0001](docs/adr/0001-api-gateway-routing.md)) пока не доступна
через `make up` — роутит корректно, но ни один из семи сервисов ещё не
контейнеризован и не добавлен в `docker-compose.yaml` (шаг 5 роадмапа
ниже).

## Технологический роадмап

Начальная инфраструктура:

- Docker Compose
- Nginx
- PostgreSQL
- RabbitMQ

Стек приложения:

- PHP
- Laravel
- PostgreSQL
- Redis
- RabbitMQ

Архитектура:

- Clean Architecture
- Hexagonal Architecture
- Domain-Driven Design
- Event-Driven Architecture

Надёжность:

- Transactional Outbox
- Inbox / Idempotent Consumer
- Idempotency Keys
- Retry policies
- Dead Letter Queues
- Saga / Process Manager

Observability:

- OpenTelemetry
- Prometheus
- Grafana
- Tempo
- Loki

Деплой:

- Docker
- CI/CD
- Kubernetes

## Статус

Активно в разработке.

Все семь сервисов существуют (Identity, Customer, Catalog, Subscription,
Billing, Payment, Notification), каждый — полностью рабочее
Clean/Hexagonal Laravel-приложение со своими тестами. Новых сервисов не
планируется — бизнес-декомпозиция завершена. Осталось превратить эти
семь Laravel-приложений в настоящую production-style платформу,
примерно в таком порядке:

1. **API Gateway / Ingress** — дизайн роутинга/auth-границы завершён
   (см. [ADR 0001](docs/adr/0001-api-gateway-routing.md)) и доступен
   end-to-end в кластере `kind` (Ingress → gateway → каждый сервис);
   через локальный `docker-compose` (`make up`) пока всё ещё
   недоступен, поскольку ни один сервис не подключён к этому
   compose-файлу (шаг 5).
2. RabbitMQ-топология — контракт messaging, конвенции именования,
   envelope и правила топологии очередей зафиксированы
   ([ADR 0002](docs/adr/0002-rabbitmq-messaging.md) +
   [каталог событий](docs/architecture/event-catalog.md)),
   формализуя то, что пять сервисов уже делали по подобию. Основная
   цепочка событий платформы теперь связана end-to-end (Subscription →
   Billing → Payment → Billing/Subscription/Notification), включая
   трансляцию Billing исходов платежей в события с `subscription_id`
   для Subscription, и Outbox catalog-service, реально доходящий до
   RabbitMQ (раньше упирался в publisher, который только логировал).
   Retry/DLQ policy, publisher confirms и prefetch ещё не построены.
3. Distributed reliability — собрать уже используемые по сервисам
   паттерны Outbox/Inbox/idempotency в единый набор платформенных
   правил, и реально протестировать crash-сценарии (падение до ack,
   падение до отметки outbox, повторная доставка, недоступность
   брокера/БД, таймаут провайдера).
4. Observability — трейсы/метрики/логи OpenTelemetry, correlation ID,
   прокидываемые через заголовки RabbitMQ, Grafana/Tempo/Prometheus/Loki.
5. Docker / локальное окружение — Dockerfile готовы для всех семи
   сервисов; записи в `docker-compose.yaml` (чтобы `make up` реально
   было куда роутить с gateway) ещё не написаны. Два тестовых
   compose-стека под `tests/integration/*/docker-compose.yaml` — не
   замена: они существуют, чтобы гонять один конкретный тестовый сьют,
   а не для повседневной локальной разработки.
6. Kubernetes — сделано для основного вертикального среза RabbitMQ:
   все семь сервисов работают в локальном кластере `kind`
   (`infrastructure/kubernetes/`), каждый разбит на правильные workload
   (API Deployment, плюс Consumer и/или Outbox Deployment для тех, у
   кого есть messaging-роль — не один Pod на сервис), с health probes,
   PodDisruptionBudget-ами и topology spread. Манифесты NetworkPolicy и
   HPA/KEDA существуют, но локально не применяются (CNI в `kind` не
   умеет NetworkPolicy, а metrics-server отсутствует) — см.
   `infrastructure/kubernetes/platform/README.md`.
7. CI/CD — pipeline на сервис в monorepo-aware сборке (lint,
   статический анализ, слои тестов, build, scan, deploy, migrate, smoke
   test), запускается только для реально изменившихся сервисов.
8. Contract + end-to-end тестирование — полная пирамида, а не один
   большой E2E-сьют: Unit/Application/Integration на сервис (уже есть),
   плюс новые уровни Component, Contract, Service integration, E2E и
   Resilience, строящиеся по одному вертикальному срезу за раз — так
   же, как строилась сама цепочка RabbitMQ
   ([ADR 0004](docs/adr/0004-testing-strategy.ru.md) +
   [стратегия тестирования](docs/architecture/testing-strategy.ru.md)).
   Готово четыре service integration среза (реальный Postgres, реальный
   RabbitMQ, без моков): `subscription-to-billing`,
   `billing-to-payment`, `payment-to-billing` и
   `billing-to-subscription`, в
   [`tests/integration/`](tests/integration/) — уже есть каждый срез,
   нужный первому сценарию `tests/e2e/`.
9. Security hardening.
10. Load / failure тестирование.
