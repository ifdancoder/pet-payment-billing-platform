# 1. Маршрутизация API gateway и разделение public/internal

*[English version](0001-api-gateway-routing.md)*

## Статус

Accepted

## Контекст

Клиентам нужен стабильный endpoint, не раскрывающий имена и порты сервисов.
Access tokens самодостаточны и проверяются каждым сервисом, поэтому routing не
должен добавлять синхронную зависимость от identity-service. Внутренние маршруты
сервисов используют префикс `/api/v1`.

## Решение

Nginx является единственной внешней точкой входа. Он публикует `/v1`,
переписывает запросы на `/api/v1` и маршрутизирует их по ресурсу:

| Публичный путь | Сервис |
| --- | --- |
| `/v1/merchants`, `/v1/users`, `/v1/auth/*` | identity-service |
| `/v1/merchants/{merchant}/memberships*` | identity-service |
| `/v1/merchants/{merchant}/api-keys*` | identity-service |
| `/v1/merchants/{merchant}/customers*` | customer-service |
| `/v1/merchants/{merchant}/products*`, `prices*` | catalog-service |
| `/v1/merchants/{merchant}/subscriptions*` | subscription-service |
| `/v1/merchants/{merchant}/invoices*` | billing-service |
| `/v1/merchants/{merchant}/payments*` | payment-service |
| `/v1/merchants/{merchant}/notifications*` | notification-service |

Для неизвестных путей возвращается `503 {"error":"no_services_available"}`.
Gateway передаёт authorization и correlation headers, но не аутентифицирует
запрос. Access token, tenant membership и roles проверяет целевой сервис.

Upstream names разрешаются во время запроса. Отдельные resolver и DNS suffix
files позволяют использовать одну конфигурацию routing в Compose и Kubernetes.

## Последствия

Клиенты используют один host и стабильную схему путей. Разделение или слияние
сервисов меняет routing table, но не public paths. Новый маршрут требует
согласованных изменений Nginx и OpenAPI; `make test-docs` сверяет OpenAPI с
зарегистрированными маршрутами. На каждом защищённом маршруте сервиса обязателен
authentication middleware.
