# 1. API gateway routing and the public/internal split

*[Русская версия](0001-api-gateway-routing.ru.md)*

## Status

Accepted

## Context

All seven services now exist, each independently listening on its own
port during local development and each serving its own routes under
`/api/v1/...`. Nothing sits in front of them: a client has to know
`identity-service:8000`, `customer-service:8001`, and so on, and pick the
right one itself. That's not a contract we want to expose — it leaks
deployment topology (which services exist, how many there are, what
port each one is on) into every client, and it means "add a service" or
"split a service in two" become breaking changes for everyone calling
the platform.

Two more things forced this decision one way rather than another:

- **Identity is deliberately not a synchronous dependency of every
  request.** Access tokens are meant to be self-contained and verified
  locally by whichever service receives them (see identity-service's
  own design notes, point 13-14/62: "Identity не должен стать SPOF").
  Anything that makes every request synchronously depend on Identity
  recreates that single point of failure one layer up, even if Identity
  itself never gets touched again after login.
- **No service has a genuinely internal-only endpoint yet.** The
  HTTP calls other services already make to each other
  (`HttpCustomerGateway`, `HttpCustomerContactGateway`,
  `HttpCatalogGateway`) hit the exact same routes a merchant-facing
  client would use. There's no `/internal/...` convention anywhere in
  the codebase today.

## Decision

**A single Nginx gateway is the only entry point from outside the
platform.** Locally it's the `gateway` service in `docker-compose.yaml`;
on Kubernetes it's the same image behind `ingress-nginx`
(`infrastructure/kubernetes/platform/ingress/ingress.yaml` already
forwards every path to a `gateway` Service — this ADR is what fills in
what that gateway actually does, no changes needed on the Kubernetes
side).

**Public contract is `/v1/...`, not `/api/v1/...`.** The gateway
rewrites `/v1/X` to `/api/v1/X` before proxying
(`infrastructure/nginx/nginx.conf`). `/api` is an internal
implementation detail — dropping it from the public contract means a
service's own route prefix can change without that being a breaking
change for clients, and keeps the public surface from ever suggesting
there's a single monolithic API behind it.

**Routing is by resource prefix, decided from each service's actual,
already-implemented routes** (`grep`-ed from
`app/Presentation/Http/V1/Routes/*.php` across every service, not
reconstructed from a target design), one Nginx `location` block per
resource:

| Public path | Service | Notes |
| --- | --- | --- |
| `POST /v1/merchants` | identity-service | Exact match — creating a Merchant has no tenant context to nest under |
| `POST /v1/users` | identity-service | Same reasoning |
| `/v1/auth/*` | identity-service | Registration, login, refresh-token rotation/logout, and API-key exchange |
| `/v1/merchants/{merchant}/memberships*` | identity-service | Owner/admin membership administration |
| `/v1/merchants/{merchant}/api-keys*` | identity-service | Hashed, revocable API-key lifecycle |
| `/v1/merchants/{merchant}/customers*` | customer-service | Tenant is taken exclusively from the authenticated path context |
| `/v1/merchants/{merchant}/products*` | catalog-service | |
| `/v1/merchants/{merchant}/prices*` | catalog-service | |
| `/v1/merchants/{merchant}/subscriptions*` | subscription-service | |
| `/v1/merchants/{merchant}/invoices*` | billing-service | |
| `/v1/merchants/{merchant}/payments*` | payment-service | |
| `/v1/merchants/{merchant}/notifications*` | notification-service | |

Anything else returns `503 {"error":"no_services_available"}` — a
deliberately generic response so the gateway never confirms or denies
that some other path *would* have worked.

**The gateway does not authenticate.** It forwards the `Authorization`
header untouched and never inspects, verifies, or strips it. Every
service is responsible for verifying its own requests locally with the
shared Ed25519 verification middleware. This is the direct consequence of
the "Identity is not a SPOF" decision above: an authenticating gateway
would just be Identity-in-the-critical-path wearing a different name.

**Upstream hostnames are resolved at request time, not at Nginx
startup**, via `resolver 127.0.0.11` (Docker Compose's embedded DNS) and
`set $upstream ...; proxy_pass http://$upstream;` rather than a bare
`proxy_pass http://identity-service:8000;`. Request-time resolution keeps
the gateway available while services start, restart, roll or scale to zero;
only a request to the unavailable upstream fails with `502`.

## Consequences

**Easier:**
- Clients have one host and one stable path shape to learn regardless
  of how many services back it or how they're split.
- Splitting or merging services later doesn't break the public
  contract, only the gateway's routing table.
- The exact same routing config works locally (docker-compose) and in
  the cluster (behind ingress-nginx), so there's one source of truth
  for "what's public," not two configs to keep in sync.
- OpenAPI-to-Laravel parity checks catch drift between public contracts and
  implemented service routes.

**Harder / follow-up work:**
- The gateway config has to be hand-maintained in parallel with each
  service's actual routes; nothing generates it. A route added to a
  service without a matching gateway `location` block is unreachable
  from outside — silently, since the catch-all just returns a generic
  503.
- The gateway remains intentionally unauthenticated, so every new service
  route must attach the shared access-token, tenant and role middleware.
- The routing table is exercised both by local Docker Compose and by the
  real Ingress smoke suite in `tests/kind/`.
