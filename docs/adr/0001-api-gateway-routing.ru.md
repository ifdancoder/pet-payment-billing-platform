# 1. Маршрутизация API gateway и разделение public/internal

*[English version](0001-api-gateway-routing.md)*

## Статус

Принято

## Контекст

Все семь сервисов уже существуют. При локальной разработке каждый
независимо слушает свой порт и обслуживает собственные маршруты
`/api/v1/...`. Перед ними ничего нет: клиент должен знать адреса
`identity-service:8000`, `customer-service:8001` и остальных сервисов и
сам выбирать нужный. Такой контракт нельзя выставлять наружу: он
раскрывает клиентам топологию развёртывания — какие сервисы существуют,
сколько их и какие порты они используют. В результате добавление
сервиса или разделение одного сервиса на два становится breaking change
для всех клиентов платформы.

Ещё два обстоятельства определили решение:

- **Identity намеренно не является синхронной зависимостью каждого
  запроса.** Access tokens должны быть самодостаточными и проверяться
  локально тем сервисом, который получил запрос (см. design notes
  identity-service, пункты 13–14/62: «Identity не должен стать SPOF»).
  Если каждый запрос синхронно зависит от Identity, single point of
  failure просто появляется уровнем выше, даже если после login сам
  Identity больше не вызывается.
- **Ни в одном сервисе пока нет по-настоящему внутренних endpoints.**
  Существующие межсервисные HTTP-вызовы (`HttpCustomerGateway`,
  `HttpCustomerContactGateway`, `HttpCatalogGateway`) обращаются к тем
  же маршрутам, что и клиент мерчанта. Соглашения `/internal/...` в
  кодовой базе сейчас нет.

## Решение

**Единый Nginx gateway — единственная точка входа извне платформы.**
Локально это сервис `gateway` в `docker-compose.yaml`; в Kubernetes —
тот же образ за `ingress-nginx`. Существующий
`infrastructure/kubernetes/platform/ingress/ingress.yaml` уже направляет
все пути в Service `gateway`; этот ADR определяет поведение gateway, не
требуя изменений на стороне Kubernetes.

**Публичный контракт — `/v1/...`, а не `/api/v1/...`.** Gateway
переписывает `/v1/X` в `/api/v1/X` перед проксированием
(`infrastructure/nginx/nginx.conf`). `/api` — внутренняя деталь
реализации. Если убрать её из публичного контракта, префикс маршрутов
сервиса можно менять без breaking change для клиентов, а внешняя
поверхность не создаёт впечатление единого монолитного API.

**Маршрутизация по префиксу ресурса определяется фактически
реализованными маршрутами каждого сервиса.** Они собраны из
`app/Presentation/Http/V1/Routes/*.php` всех сервисов, а не восстановлены
по целевому дизайну. На каждый ресурс приходится один блок Nginx
`location`:

| Публичный путь | Сервис | Примечания |
| --- | --- | --- |
| `POST /v1/merchants` | identity-service | Точное совпадение: у создания Merchant ещё нет tenant context для вложенного пути |
| `POST /v1/users` | identity-service | Та же причина |
| `/v1/auth/*` | identity-service | Registration, login, refresh rotation/logout и обмен API key |
| `/v1/merchants/{merchant}/memberships*` | identity-service | Управление memberships для owner/admin |
| `/v1/merchants/{merchant}/api-keys*` | identity-service | Жизненный цикл хешированных, отзываемых API keys |
| `/v1/merchants/{merchant}/customers*` | customer-service | Tenant берётся только из аутентифицированного path context |
| `/v1/merchants/{merchant}/products*` | catalog-service | |
| `/v1/merchants/{merchant}/prices*` | catalog-service | |
| `/v1/merchants/{merchant}/subscriptions*` | subscription-service | |
| `/v1/merchants/{merchant}/invoices*` | billing-service | |
| `/v1/merchants/{merchant}/payments*` | payment-service | |
| `/v1/merchants/{merchant}/notifications*` | notification-service | |

На любой другой путь возвращается
`503 {"error":"no_services_available"}`. Ответ намеренно общий, чтобы
gateway не подтверждал и не опровергал существование другого возможного
маршрута.

**Gateway не выполняет аутентификацию.** Он передаёт заголовок
`Authorization` без изменений и никогда не проверяет и не удаляет его.
Каждый сервис локально проверяет запросы общим Ed25519 middleware. Это
прямое следствие
решения «Identity не является SPOF»: аутентифицирующий gateway оказался
бы тем же Identity в critical path под другим именем.

**Имена upstream разрешаются во время запроса, а не при запуске
Nginx**, через `resolver 127.0.0.11` (встроенный DNS Docker Compose) и
`set $upstream ...; proxy_pass http://$upstream;`, а не прямой
`proxy_pass http://identity-service:8000;`. Разрешение во время запроса
сохраняет gateway доступным при старте, рестарте, rolling deploy и
autoscaling to zero; только запрос к недоступному upstream получает `502`.

## Последствия

**Проще:**

- Клиентам достаточно знать один host и одну стабильную форму путей,
  независимо от числа и внутреннего разбиения сервисов.
- Разделение или объединение сервисов не ломает публичный контракт —
  меняется только таблица маршрутов gateway.
- Одинаковая конфигурация маршрутизации работает локально через Docker
  Compose и в кластере за ingress-nginx. Источник истины о публичных
  путях один, а не два.
- Проверка parity OpenAPI ↔ Laravel обнаруживает drift публичного контракта
  и фактических service routes.

**Сложнее / дальнейшая работа:**

- Конфигурацию gateway нужно вручную синхронизировать с маршрутами
  сервисов; генератора нет. Endpoint без соответствующего блока
  `location` будет недоступен извне, причём незаметно: catch-all вернёт
  общий 503.
- Gateway остаётся намеренно неаутентифицирующим, поэтому каждый новый
  service route обязан подключать общие access-token, tenant и role middleware.
- Таблица маршрутов проверяется локальным Docker Compose и настоящим
  Ingress smoke suite в `tests/kind/`.
