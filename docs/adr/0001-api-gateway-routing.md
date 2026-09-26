# 1. API gateway routing and the public/internal split

*[Русская версия](0001-api-gateway-routing.ru.md)*

## Status

Accepted

## Context

Clients need a stable endpoint that does not expose service hostnames or ports.
Access tokens are self-contained and verified by each service, so routing must
not add a synchronous dependency on identity-service. Service routes use the
internal `/api/v1` prefix.

## Decision

Nginx is the only external entry point. It exposes `/v1`, rewrites requests to
`/api/v1`, and routes by resource prefix:

| Public path | Service |
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

Unmatched paths return `503 {"error":"no_services_available"}`. The gateway
forwards authorization and correlation headers without authenticating the
request. Each service verifies access tokens, tenant membership, and roles.

Upstream names are resolved at request time. Environment-specific resolver and
DNS suffix files let the same routing configuration work in Compose and
Kubernetes.

## Consequences

Clients use one host and a stable path scheme. A service split or merge changes
the routing table without changing public paths. New routes require coordinated
updates to Nginx and OpenAPI; `make test-docs` checks OpenAPI against registered
service routes. Authentication middleware remains mandatory on every protected
service route.
